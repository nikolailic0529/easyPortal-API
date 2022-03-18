<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Location;
use App\Services\Organization\CurrentOrganization;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use League\Geotools\BoundingBox\BoundingBoxInterface;
use League\Geotools\Geohash\Geohash;
use League\Geotools\Polygon\Polygon;
use Nuwave\Lighthouse\Execution\Arguments\Argument;
use Nuwave\Lighthouse\Execution\Arguments\ArgumentSet;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

use function array_filter;
use function array_unique;
use function array_values;
use function explode;
use function strlen;

class Map {
    public function __construct(
        protected CurrentOrganization $organization,
    ) {
        // empty
    }

    /**
     * @param array{
     *     level:int,
     *     boundaries:array<\League\Geotools\Geohash\Geohash>,
     *     locations:array<mixed>,
     *     assets:array<mixed>
     *     } $args
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): Collection {
        // Base query and Viewport
        $boundaries = $this->getBoundaries($args['boundaries'] ?? []);
        $locations  = $this->getArgumentSet($resolveInfo->argumentSet->arguments['locations'] ?? null);
        $model      = new Location();
        $base       = Location::query()
            ->whereNotNull($model->qualifyColumn('geohash'))
            ->whereNotNull($model->qualifyColumn('latitude'))
            ->whereNotNull($model->qualifyColumn('longitude'));

        // Apply where conditions
        $keyname = $model->getQualifiedKeyName();
        $level   = $args['level'];
        $query   = (clone $base)
            ->select([
                new Expression("AVG({$model->qualifyColumn('latitude')}) as latitude"),
                new Expression("AVG({$model->qualifyColumn('longitude')}) as longitude"),
                new Expression("GROUP_CONCAT({$keyname} ORDER BY {$keyname} SEPARATOR ',') as locations_ids"),
            ])
            ->when(
                $level < Location::GEOHASH_LENGTH,
                static function (Builder $builder) use ($model, $level): void {
                    $builder->selectRaw("SUBSTRING({$model->qualifyColumn('geohash')}, 1, {$level}) as hash");
                },
                static function (Builder $builder) use ($model): void {
                    $builder->addSelect("{$model->qualifyColumn('geohash')} as hash");
                },
            )
            ->groupBy('hash')
            ->limit(1000);

        // Apply where conditions
        $where = $this->getArgumentSet($resolveInfo->argumentSet->arguments['assets'] ?? null);
        $query = $query
            ->selectRaw('COUNT(DISTINCT data.`customer_id`) as `customers_count`')
            ->selectRaw(
                "GROUP_CONCAT(DISTINCT data.`customer_id` ORDER BY data.`customer_id` SEPARATOR ',') as customers_ids",
            )
            ->whereNotNull('data.customer_id')
            ->whereNotNull('data.location_id');

        if ($where instanceof ArgumentSet) {
            // If conditions are specified we need to join assets. In this case,
            // we can filter them by Location and do not add locations filters
            // into the main query.
            $base  = $this->applyBoundaries($base, $locations, $boundaries);
            $query = $query->joinRelation(
                'assets',
                'data',
                static function (HasMany $relation, EloquentBuilder $builder) use ($base, $where): EloquentBuilder {
                    $query           = (clone $base)->select($base->getModel()->getKeyName());
                    $foreignKeyName  = $relation->getQualifiedForeignKeyName();
                    $customerKeyName = $relation->newModelInstance()->qualifyColumn('customer_id');

                    return $where
                        ->enhanceBuilder($builder, [])
                        ->distinct()
                        ->select([
                            new Expression($foreignKeyName),
                            new Expression("{$customerKeyName} as customer_id"),
                        ])
                        ->whereIn('location_id', $query);
                },
            );
        } else {
            // If no conditions we can use `location_customers` but we should
            // also add locations filters into the main query.
            $query = $this->applyBoundaries($query, $locations, $boundaries);
            $query = $query->joinRelation(
                'customers',
                'data',
                static function (BelongsToMany $relation, EloquentBuilder $builder): EloquentBuilder {
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

        // Process
        $locations = $query->get();

        foreach ($locations as $location) {
            $boundingBox             = (new Geohash())->decode($location->hash)->getBoundingBox();
            $location->customers_ids = $this->parseKeys($location->customers_ids ?? null);
            $location->locations_ids = $this->parseKeys($location->locations_ids ?? null);
            $location->boundingBox   = [
                'southLatitude' => $boundingBox[0]->getLatitude(),
                'northLatitude' => $boundingBox[1]->getLatitude(),
                'westLongitude' => $boundingBox[0]->getLongitude(),
                'eastLongitude' => $boundingBox[1]->getLongitude(),
            ];
        }

        // Return
        return $locations;
    }

    /**
     * @return array<string>
     */
    protected function parseKeys(?string $keys): array {
        return array_values(array_unique(array_filter(explode(',', (string) $keys), static function ($id) {
            return strlen($id) === 36;
        })));
    }

    protected function getArgumentSet(?Argument $argument): ?ArgumentSet {
        $set = null;

        if ($argument && $argument->value instanceof ArgumentSet) {
            // `@searchBy` directive is associated with Argument, so we need to
            // add it into new ArgumentSet.
            $set                     = new ArgumentSet();
            $set->directives         = new Collection();
            $set->arguments['where'] = $argument;
        }

        return $set;
    }

    /**
     * @param array<Geohash> $geohashes
     */
    protected function getBoundaries(array $geohashes): ?BoundingBoxInterface {
        $boundaries = null;

        if ($geohashes) {
            $points     = (new Collection($geohashes))
                ->map(static fn(Geohash $geohash): array => $geohash->getBoundingBox())
                ->flatten(1)
                ->all();
            $polygon    = new Polygon($points);
            $boundaries = $polygon->getBoundingBox();
        }

        return $boundaries;
    }

    protected function applyBoundaries(
        EloquentBuilder $builder,
        ArgumentSet|BoundingBoxInterface|null ...$boundaries,
    ): EloquentBuilder {
        foreach ($boundaries as $boundary) {
            if ($boundary instanceof BoundingBoxInterface) {
                $builder = $builder
                    ->whereBetween('latitude', Arr::sort([$boundary->getNorth(), $boundary->getSouth()]))
                    ->whereBetween('longitude', Arr::sort([$boundary->getEast(), $boundary->getWest()]));
            } elseif ($boundary instanceof ArgumentSet) {
                $builder = $boundary->enhanceBuilder($builder, []);
            } else {
                // empty
            }
        }

        return $builder;
    }
}
