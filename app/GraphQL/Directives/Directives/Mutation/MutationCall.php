<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation;

use App\GraphQL\Directives\Directives\Mutation\Context\Context;
use App\GraphQL\Directives\Directives\Mutation\Rules\ContextAwareRule;
use App\GraphQL\Directives\Directives\Mutation\Rules\CustomRule;
use App\GraphQL\Directives\Directives\Mutation\Rules\LaravelRule;
use App\GraphQL\Directives\Directives\Mutation\Rules\Rule as RuleDirective;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException as LaravelValidationException;
use Nuwave\Lighthouse\Exceptions\DefinitionException;
use Nuwave\Lighthouse\Exceptions\ValidationException;
use Nuwave\Lighthouse\Execution\Arguments\ArgumentSet;
use Nuwave\Lighthouse\Execution\Arguments\ListType;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldResolver;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Nuwave\Lighthouse\Support\Utils;

use function array_filter;
use function array_merge;
use function array_slice;
use function current;
use function implode;
use function is_bool;
use function reset;
use function sprintf;

abstract class MutationCall extends BaseDirective implements FieldResolver {
    protected const NAME              = 'mutationCall';
    protected const ARGUMENT_RESOLVER = 'resolver';

    public function __construct(
        protected Factory $factory,
    ) {
        // empty
    }

    public static function definition(): string {
        $name     = static::NAME;
        $resolver = static::ARGUMENT_RESOLVER;

        return /** @lang GraphQL */ <<<GRAPHQL
            """
            Indicates that field is the mutation.
            """
            directive @{$name}(
                """
                Reference to a function that executes mutation.
                """
                {$resolver}: String!
            ) on FIELD_DEFINITION
            GRAPHQL;
    }

    public function resolveField(FieldValue $fieldValue): FieldValue {
        return $fieldValue->setResolver(
            function (Context $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): mixed {
                return $this->validate($root, $args, $context, $resolveInfo)
                    ? $this->resolve($this->getContext($root)?->getRoot(), $args, $context, $resolveInfo)
                    : ['result' => false];
            },
        );
    }

    protected function getContext(Context $context): ?Context {
        return $context->getContext();
    }

    /**
     * @param array<mixed> $args
     */
    protected function resolve(
        mixed $root,
        array $args,
        GraphQLContext $context,
        ResolveInfo $resolveInfo,
    ): mixed {
        $resolver = $this->getResolverFromArgument(static::ARGUMENT_RESOLVER);
        $result   = $resolver($root, $args, $context, $resolveInfo);
        $field    = current(array_slice($resolveInfo->path, -2, 1));

        if ($result instanceof Model) {
            $result = [
                'result' => true,
                $field   => $result,
            ];
        } elseif (is_bool($result)) {
            $result = [
                'result' => $result,
                $field   => $root,
            ];
        } else {
            // as is
        }

        return $result;
    }

    /**
     * @param array<mixed> $args
     */
    protected function validate(Context $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): bool {
        try {
            $rules = $this->getRules($root, $resolveInfo->argumentSet);

            if ($rules) {
                $this->factory->make($args, $rules)->validate();
            }
        } catch (LaravelValidationException $exception) {
            throw ValidationException::fromLaravel($exception);
        }

        return true;
    }

    /**
     * @return array<string, array<\Illuminate\Contracts\Validation\Rule>>
     */
    protected function getRules(Context $context, ArgumentSet $set, string $prefix = null): array {
        $rules = [];

        if ($prefix) {
            $rules = array_merge($rules, [
                $prefix => $this->getRulesFromDirectives($context, $set->directives),
            ]);
        }

        $arguments = $set->arguments;
        $rules     = array_merge($rules, $this->getRulesFromArguments($context, $prefix, $arguments));

        return array_filter($rules);
    }

    /**
     * @param array<\Nuwave\Lighthouse\Execution\Arguments\Argument> $arguments
     *
     * @return array<\Illuminate\Contracts\Validation\Rule>
     */
    private function getRulesFromArguments(Context $context, ?string $prefix, array $arguments): array {
        $rules = [];

        foreach ($arguments as $name => $argument) {
            $key         = $prefix ? "{$prefix}.{$name}" : $name;
            $rules[$key] = $this->getRulesFromDirectives($context, $argument->directives);

            if ($argument->type instanceof ListType && $argument->value) {
                $rules = array_merge($rules, $this->getRules($context, reset($argument->value), "{$key}.*"));
            } elseif ($argument->value instanceof ArgumentSet) {
                $rules = array_merge($rules, $this->getRules($context, $argument->value, $key));
            } else {
                // empty
            }
        }

        return $rules;
    }

    /**
     * @param \Illuminate\Support\Collection<\Nuwave\Lighthouse\Support\Contracts\Directive> $directives
     *
     * @return array<string,\Illuminate\Contracts\Validation\Rule>
     */
    private function getRulesFromDirectives(Context $context, Collection $directives): array {
        return $directives
            ->filter(Utils::instanceofMatcher(RuleDirective::class))
            ->map(static function (RuleDirective $directive) use ($context): Rule|string {
                $rule = null;

                if ($directive instanceof CustomRule) {
                    $rule = $directive->getRule();
                } elseif ($directive instanceof LaravelRule) {
                    $rule = $directive->getRule();
                } elseif ($directive instanceof Rule) {
                    $rule = $directive;
                } else {
                    throw new DefinitionException(sprintf(
                        'Directive `@%s` must be one of `%s`.',
                        $directive->name(),
                        implode('`, `', [
                            LaravelRule::class,
                            CustomRule::class,
                            Rule::class,
                        ]),
                    ));
                }

                if ($rule instanceof ContextAwareRule) {
                    $rule->setMutationContext($context);
                }

                return $rule;
            })
            ->all();
    }
}
