<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Map;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Collection;
use stdClass;

class Assets {
    public function __construct() {
        // empty
    }

    /**
     * @return Collection<int, stdClass>
     */
    public function __invoke(MapBuilder $builder): Collection {
        // Base query
        $query = $builder->getQuery('data.asset_id');
        $query = $builder->applyBoundariesConditions($query)
            ->whereNotNull('data.asset_id')
            ->joinRelation(
                'assets',
                'data',
                static function (HasMany $relation, Builder $query) use ($builder): Builder {
                    $foreignKeyName = $relation->getQualifiedForeignKeyName();
                    $objectKeyName  = $relation->newModelInstance()->getQualifiedKeyName();

                    return $builder
                        ->applyAssetsConditions($query)
                        ->distinct()
                        ->select([
                            new Expression($foreignKeyName),
                            new Expression("{$objectKeyName} as asset_id"),
                        ]);
                },
            );

        // Return
        return $builder->getPoints($query);
    }
}
