<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives;

use Nuwave\Lighthouse\Schema\Directives\RelationDirective;

abstract class Relation extends RelationDirective {
    public static function definition(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            """
            Eloquent relationship.
            """
            directive @relation(
                """
                Relationship name if it is named different from the field in the schema.
                """
                relation: String
            ) on FIELD_DEFINITION
            GRAPHQL;
    }
}
