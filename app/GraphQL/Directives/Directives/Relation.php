<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation as EloquentRelation;
use Nuwave\Lighthouse\Schema\Directives\RelationDirective;

use function in_array;

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

    /**
     * @param EloquentRelation<Model> $relation
     */
    protected function isSameConnection(EloquentRelation $relation): bool {
        return parent::isSameConnection($relation)
            || $this->isSafeRelation($relation);
    }

    /**
     * @param EloquentRelation<Model> $relation
     */
    protected function isSafeRelation(EloquentRelation $relation): bool {
        // Some relations like `HasMany` use `IN (<key>)` and can be used across
        // different connections.
        return in_array($relation::class, [HasMany::class], true);
    }
}
