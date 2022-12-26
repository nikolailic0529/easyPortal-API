<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation;

use App\GraphQL\Directives\Directives\Mutation\Context\Context;
use App\GraphQL\Directives\Directives\Mutation\Rules\ContextAwareRule;
use App\GraphQL\Directives\Directives\Mutation\Rules\CustomRule;
use App\GraphQL\Directives\Directives\Mutation\Rules\LaravelRule;
use App\GraphQL\Directives\Directives\Mutation\Rules\Rule as RuleDirective;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\InvokableRule;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException as LaravelValidationException;
use Nuwave\Lighthouse\Exceptions\DefinitionException;
use Nuwave\Lighthouse\Exceptions\ValidationException;
use Nuwave\Lighthouse\Execution\Arguments\Argument;
use Nuwave\Lighthouse\Execution\Arguments\ArgumentSet;
use Nuwave\Lighthouse\Execution\Arguments\ListType;
use Nuwave\Lighthouse\Execution\ResolveInfo;
use Nuwave\Lighthouse\Schema\DirectiveLocator;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\Directive;
use Nuwave\Lighthouse\Support\Contracts\FieldResolver;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Nuwave\Lighthouse\Support\Utils;

use function array_combine;
use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function array_slice;
use function assert;
use function current;
use function implode;
use function is_bool;
use function is_string;
use function sprintf;
use function str_contains;
use function str_replace;

abstract class MutationCall extends BaseDirective implements FieldResolver {
    protected const NAME              = 'mutationCall';
    protected const ARGUMENT_RESOLVER = 'resolver';

    public function __construct(
        protected DirectiveLocator $directives,
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
            function (mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): mixed {
                assert($root instanceof Context);

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
            // Field
            $fieldNode  = $resolveInfo->fieldDefinition->astNode;
            $directives = $this->directives->associated($fieldNode);
            $fieldRules = $this->getRulesFromDirectives($root, $directives);

            if ($fieldRules) {
                $this
                    ->getValidator(['context' => $root], ['context' => $fieldRules])
                    ->validate();
            }

            // Arguments
            $argsRules = $this->getRules($root, $resolveInfo->argumentSet);

            if ($argsRules) {
                $this
                    ->getValidator($args, $argsRules)
                    ->validate();
            }
        } catch (LaravelValidationException $exception) {
            throw ValidationException::fromLaravel($exception);
        }

        return true;
    }

    /**
     * @return array<string, array<Rule|InvokableRule|string>>
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
     * @param array<Argument> $arguments
     *
     * @return array<string, array<Rule|InvokableRule|string>>
     */
    private function getRulesFromArguments(Context $context, ?string $prefix, array $arguments): array {
        $rules = [];

        foreach ($arguments as $name => $argument) {
            $key      = $prefix ? "{$prefix}.{$name}" : $name;
            $value    = $argument->value;
            $argRules = $this->getRulesFromDirectives($context, $argument->directives);

            if ($argument->type instanceof ListType) {
                $rules["{$key}.*"] = $argRules;

                foreach ((array) $value as $i => $v) {
                    if (!($v instanceof ArgumentSet)) {
                        continue;
                    }

                    foreach ($this->getRules($context, $v, "{$key}.{$i}") as $k => $rs) {
                        // Laravel Rules can contain references to other fields
                        // where the `*` is the current index -> we need to
                        // replace `*` by actual index.
                        //
                        // Current solution is not so good, would be good to find
                        // a better one...
                        $rules[$k] = array_map(
                            static function (
                                Rule|InvokableRule|string $rule,
                            ) use (
                                $key,
                                $i,
                            ): Rule|InvokableRule|string {
                                if (is_string($rule)) {
                                    $rule = str_replace("{$key}.*", "{$key}.{$i}", $rule);
                                }

                                return $rule;
                            },
                            $rs,
                        );
                    }
                }
            } elseif ($value instanceof ArgumentSet) {
                $rules[$key] = $argRules;
                $rules       = array_merge($rules, $this->getRules($context, $value, $key));
            } else {
                $rules[$key] = $argRules;
            }
        }

        return $rules;
    }

    /**
     * @param Collection<int, Directive> $directives
     *
     * @return array<Rule|InvokableRule|string>
     */
    private function getRulesFromDirectives(Context $context, Collection $directives): array {
        return $directives
            ->filter(Utils::instanceofMatcher(RuleDirective::class))
            ->map(static function (RuleDirective $directive) use ($context): Rule|InvokableRule|string {
                $rule = null;

                if ($directive instanceof CustomRule) {
                    $rule = $directive->getRule();
                } elseif ($directive instanceof LaravelRule) {
                    $rule = $directive->getRule();
                } elseif ($directive instanceof Rule || $directive instanceof InvokableRule) {
                    $rule = $directive;
                } else {
                    throw new DefinitionException(sprintf(
                        'Directive `@%s` must be one of `%s`.',
                        $directive->name(),
                        implode('`, `', [
                            LaravelRule::class,
                            CustomRule::class,
                            Rule::class,
                            InvokableRule::class,
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

    /**
     * @param array<string, mixed>                            $data
     * @param array<string, array<string|Rule|InvokableRule>> $rules
     *
     */
    protected function getValidator(array $data, array $rules): Validator {
        // We specify $custom attributes because Validator converts/translates
        // attribute names into readable form by default (customer_id =>
        // customer id) - it is unwanted behavior in this case.
        $names     = array_keys($rules);
        $names     = array_filter($names, static function (string $name): bool {
            return !str_contains($name, '*');
        });
        $custom    = array_combine($names, $names);
        $validator = $this->factory->make($data, $rules, [], $custom);

        return $validator;
    }
}
