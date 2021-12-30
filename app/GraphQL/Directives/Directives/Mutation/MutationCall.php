<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation;

use App\GraphQL\Directives\Directives\Mutation\Context\Context;
use App\GraphQL\Directives\Directives\Mutation\Exceptions\ValidatorException;
use App\GraphQL\Directives\Directives\Mutation\Rules\Rule;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\Model;
use Nuwave\Lighthouse\Execution\Arguments\Argument;
use Nuwave\Lighthouse\Execution\Arguments\ArgumentSet;
use Nuwave\Lighthouse\Schema\DirectiveLocator;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldResolver;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Nuwave\Lighthouse\Support\Utils;

use function array_slice;
use function current;
use function is_bool;

abstract class MutationCall extends BaseDirective implements FieldResolver {
    protected const NAME              = 'mutationCall';
    protected const ARGUMENT_RESOLVER = 'resolver';

    public function __construct(
        protected DirectiveLocator $directives,
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
                return $this->validate($root, $resolveInfo->argumentSet)
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

    protected function validate(Context $context, ArgumentSet|Argument $arguments): bool {
        $failed = $arguments->directives
            ->filter(Utils::instanceofMatcher(Rule::class))
            ->first(static function (Rule $rule) use ($context, $arguments): bool {
                $value = $arguments instanceof ArgumentSet ? $arguments->toArray() : $arguments->toPlain();
                $valid = $rule->validate($context, $value);

                return !$valid;
            });

        if ($failed) {
            throw new ValidatorException($failed->name());
        }

        if ($arguments instanceof ArgumentSet) {
            foreach ($arguments->arguments as $argument) {
                $this->validate($context, $argument);
            }
        } else {
            Utils::applyEach(
                function (mixed $value) use ($context): void {
                    if ($value instanceof ArgumentSet || $value instanceof Argument) {
                        $this->validate($context, $value);
                    }
                },
                $arguments->value,
            );
        }

        return true;
    }
}
