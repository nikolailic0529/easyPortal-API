<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives;

use App\Utils\Eloquent\Callbacks\OrderByKey;
use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation as EloquentRelation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Nuwave\Lighthouse\Execution\ResolveInfo;
use Nuwave\Lighthouse\Schema\Directives\RelationDirective;

use function in_array;

abstract class Relation extends RelationDirective {
    public function __construct(
        Repository $config,
        DatabaseManager $database,
        protected OrderByKey $orderByCallback,
    ) {
        parent::__construct($config, $database);
    }

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

    protected function makeBuilderDecorator(ResolveInfo $resolveInfo): Closure {
        $callback  = $this->orderByCallback;
        $decorator = parent::makeBuilderDecorator($resolveInfo);
        $decorator = static function (
            EloquentBuilder|EloquentRelation|QueryBuilder $builder,
        ) use (
            $callback,
            $decorator,
        ): void {
            if ($builder instanceof EloquentRelation) {
                $builder = $builder->getQuery();
            }

            $decorator($builder);

            if ($builder instanceof EloquentBuilder) {
                $callback($builder);
            }
        };

        return $decorator;
    }

    public function getRelation(): string {
        return parent::relation();
    }
}
