<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Map;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Collection;
use stdClass;

class Customers {
    public function __construct() {
        // empty
    }

    /**
     * @return Collection<int, stdClass>
     */
    public function __invoke(MapBuilder $builder): Collection {
        // Apply where conditions
        $base  = $builder->getBaseQuery();
        $query = $builder->getQuery('data.customer_id')
            ->whereNotNull('data.customer_id')
            ->whereNotNull('data.location_id');

        if ($builder->hasAssetsConditions()) {
            // If conditions are specified we need to join assets. In this case,
            // we can filter them by Location and do not add locations filters
            // into the main query.
            $base  = $builder->applyBoundariesConditions($base);
            $query = $query->joinRelation(
                'assets',
                'data',
                static function (HasMany $relation, Builder $query) use ($builder, $base): Builder {
                    $locations       = (clone $base)->select($base->getModel()->getKeyName());
                    $foreignKeyName  = $relation->getQualifiedForeignKeyName();
                    $customerKeyName = $relation->newModelInstance()->qualifyColumn('customer_id');

                    return $builder
                        ->applyAssetsConditions($query)
                        ->distinct()
                        ->select([
                            new Expression($foreignKeyName),
                            new Expression("{$customerKeyName} as customer_id"),
                        ])
                        ->whereIn('location_id', $locations);
                },
            );
        } else {
            // If no conditions we can use `location_customers` but we should
            // also add locations filters into the main query.
            $query = $builder->applyBoundariesConditions($query);
            $query = $query->joinRelation(
                'customers',
                'data',
                static function (BelongsToMany $relation, Builder $builder): Builder {
                    $pivotKeyName    = $relation->getQualifiedForeignPivotKeyName();
                    $customerKeyName = $relation->getQualifiedRelatedKeyName();

                    return $builder
                        ->select([
                            new Expression($pivotKeyName),
                            new Expression("{$customerKeyName} as customer_id"),
                        ]);
                },
            );
        }

        // Return
        return $builder->getPoints($query);
    }
}
