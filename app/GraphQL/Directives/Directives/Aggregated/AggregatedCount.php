<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Aggregated;

use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldResolver;

abstract class AggregatedCount extends BaseDirective implements FieldResolver {
    public static function definition(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            """
            Returns `count(*)`.
            """
            directive @aggregatedCount on FIELD_DEFINITION
            GRAPHQL;
    }

    public function resolveField(FieldValue $fieldValue): FieldValue {
        return $fieldValue->setResolver(
            static function (BuilderValue $root): int {
                return $root->getBuilder()->count();
            },
        );
    }
}
