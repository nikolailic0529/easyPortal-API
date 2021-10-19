<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Location;
use App\Services\Organization\CurrentOrganization;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use League\Geotools\Geotools;
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
     * @param array{level:int,viewport:array<mixed>,where:array<mixed>} $args
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): Collection {
        // Base query and Viewport
        $viewport = $this->getArgumentSet($resolveInfo->argumentSet->arguments['viewport'] ?? null);
        $level    = $args['level'];
        $model    = new Location();
        $keyname  = $model->getQualifiedKeyName();
        $query    = Location::query()
            ->selectRaw("AVG({$model->qualifyColumn('latitude')}) as latitude")
            ->selectRaw("AVG({$model->qualifyColumn('longitude')}) as longitude")
            ->selectRaw("GROUP_CONCAT({$keyname} ORDER BY {$keyname} SEPARATOR ',') as locations_ids")
            ->when(
                $level < Location::GEOHASH_LENGTH,
                static function (Builder $builder) use ($model, $level): void {
                    $builder->selectRaw("SUBSTRING({$model->qualifyColumn('geohash')}, 1, {$level}) as hash");
                },
                static function (Builder $builder) use ($model): void {
                    $builder->addSelect("{$model->qualifyColumn('geohash')} as hash");
                },
            )
            ->whereNotNull($model->qualifyColumn('geohash'))
            ->whereNotNull($model->qualifyColumn('latitude'))
            ->whereNotNull($model->qualifyColumn('longitude'))
            ->groupBy('hash')
            ->limit(1000);

        if ($viewport instanceof ArgumentSet) {
            $query = $viewport->enhanceBuilder($query, []);
        }

        // Apply where conditions
        $where = $this->getArgumentSet($resolveInfo->argumentSet->arguments['where'] ?? null);
        $query = $query
            ->selectRaw('COUNT(DISTINCT data.`customer_id`) as `customers_count`')
            ->selectRaw(
                "GROUP_CONCAT(DISTINCT data.`customer_id` ORDER BY data.`customer_id` SEPARATOR ',') as customers_ids",
            )
            ->whereNotNull('data.customer_id')
            ->whereNotNull('data.location_id');

        if ($where instanceof ArgumentSet) {
            // If conditions specified we need to join assets
            $query = $query->joinRelation(
                'assets',
                'data',
                static function (HasMany $relation, EloquentBuilder $builder) use ($where): EloquentBuilder {
                    $foreignKeyName  = $relation->getQualifiedForeignKeyName();
                    $customerKeyName = $relation->newModelInstance()->qualifyColumn('customer_id');

                    return $where
                        ->enhanceBuilder($builder, [])
                        ->distinct()
                        ->selectRaw($foreignKeyName)
                        ->selectRaw("{$customerKeyName} as customer_id");
                },
            );
        } else {
            // If no conditions we can use `location_customers`
            $query = $query->joinRelation(
                'customers',
                'data',
                static function (BelongsToMany $relation, EloquentBuilder $builder): EloquentBuilder {
                    $pivotKeyName    = $relation->getQualifiedForeignPivotKeyName();
                    $customerKeyName = $relation->getQualifiedRelatedKeyName();

                    return $builder
                        ->selectRaw($pivotKeyName)
                        ->selectRaw("{$customerKeyName} as customer_id");
                },
            );
        }

        // Process
        $locations = $query->get();
        $geotools  = new Geotools();

        foreach ($locations as $location) {
            $boundingBox             = $geotools->geohash()->decode($location->hash)->getBoundingBox();
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
}
