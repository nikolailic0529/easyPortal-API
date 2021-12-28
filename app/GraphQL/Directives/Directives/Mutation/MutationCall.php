<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation;

use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\Model;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldResolver;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

use function array_slice;
use function current;
use function is_bool;

abstract class MutationCall extends BaseDirective implements FieldResolver {
    protected const NAME              = 'mutationCall';
    protected const ARGUMENT_RESOLVER = 'resolver';

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
                return $this->resolve($this->getRoot($root), $args, $context, $resolveInfo);
            },
        );
    }

    protected function getRoot(Context $context): ?Model {
        return $context->getModel() ?? $context->getParent();
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
}
