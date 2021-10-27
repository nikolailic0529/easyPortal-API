<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Paginated;

use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Collection;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldResolver;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

abstract class Paginated extends Base implements FieldResolver {
    public static function definition(): string {
        $arguments = static::getArgumentsDefinition();

        return /** @lang GraphQL */ <<<GRAPHQL
            """
            Adds offset-based pagination for the field.
            """
            directive @paginated({$arguments}) on FIELD_DEFINITION
        GRAPHQL;
    }

    public function resolveField(FieldValue $fieldValue): FieldValue {
        return $fieldValue->setResolver(
            function (mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): Collection {
                return $this->getBuilder($root, $args, $context, $resolveInfo)->get();
            },
        );
    }
}
