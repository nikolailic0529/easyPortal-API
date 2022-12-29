<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Paginated;

use Illuminate\Support\Collection;
use Nuwave\Lighthouse\Execution\ResolveInfo;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldResolver;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Str;

abstract class Paginated extends Base implements FieldResolver {
    public const NAME = 'Paginated';

    public static function definition(): string {
        $name      = Str::lcfirst(Str::studly(static::NAME));
        $arguments = static::getArgumentsDefinition();

        return /** @lang GraphQL */ <<<GRAPHQL
            """
            Adds offset-based pagination for the field.
            """
            directive @{$name}({$arguments}) on FIELD_DEFINITION
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
