<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Map;

use App\GraphQL\Directives\Directives\Cached\ParentValue;
use App\Models\Asset;
use App\Models\Data\Location;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use League\Geotools\BoundingBox\BoundingBoxInterface;
use League\Geotools\Geohash\Geohash;
use League\Geotools\Polygon\Polygon;
use Nuwave\Lighthouse\Execution\Arguments\Argument;
use Nuwave\Lighthouse\Execution\Arguments\ArgumentSet;
use Nuwave\Lighthouse\Execution\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use stdClass;

use function array_filter;
use function array_unique;
use function array_values;
use function count;
use function explode;
use function max;
use function strlen;

class MapBuilder extends ParentValue {
    protected MapInput $input;

    /**
     * @inheritDoc
     */
    public function __construct(
        mixed $root,
        array $args,
        GraphQLContext $context,
        ResolveInfo $resolveInfo,
    ) {
        parent::__construct($root, $args, $context, $resolveInfo);

        $this->input = new MapInput($args);
    }

    public function getInput(): MapInput {
        return $this->input;
    }

    /**
     * @param Builder<Location> $builder
     *
     * @return Collection<int,stdClass>
     */
    public function getPoints(Builder $builder): Collection {
        $points = $builder->toBase()->get();

        foreach ($points as $point) {
            /** @var stdClass $point */
            $boundingBox            = (new Geohash())->decode($point->hash)->getBoundingBox();
            $point->objects_ids     = $this->parseKeys($point->objects_ids ?? null);
            $point->objects_count   = max(count($point->objects_ids), $point->objects_count ?? 0);
            $point->locations_ids   = $this->parseKeys($point->locations_ids ?? null);
            $point->locations_count = count($point->locations_ids);
            $point->boundingBox     = [
                'southLatitude' => $boundingBox[0]->getLatitude(),
                'northLatitude' => $boundingBox[1]->getLatitude(),
                'westLongitude' => $boundingBox[0]->getLongitude(),
                'eastLongitude' => $boundingBox[1]->getLongitude(),
            ];
        }

        // Return
        return $points;
    }

    /**
     * @return Builder<Location>
     */
    public function getBaseQuery(): Builder {
        $model = new Location();
        $query = Location::query()
            ->whereNotNull($model->qualifyColumn('geohash'))
            ->whereNotNull($model->qualifyColumn('latitude'))
            ->whereNotNull($model->qualifyColumn('longitude'));

        return $query;
    }

    /**
     * @return Builder<Location>
     */
    public function getQuery(string $column): Builder {
        $base    = $this->getBaseQuery();
        $model   = $base->getModel();
        $level   = $this->getInput()->level;
        $column  = $base->getGrammar()->wrap($column);
        $keyname = $model->getQualifiedKeyName();
        $query   = $base
            ->select([
                new Expression("AVG({$model->qualifyColumn('latitude')}) as latitude"),
                new Expression("AVG({$model->qualifyColumn('longitude')}) as longitude"),
                new Expression("GROUP_CONCAT({$keyname} ORDER BY {$keyname} SEPARATOR ',') as locations_ids"),
                $level < Location::GEOHASH_LENGTH
                    ? new Expression("SUBSTRING({$model->qualifyColumn('geohash')}, 1, {$level}) as hash")
                    : "{$model->qualifyColumn('geohash')} as hash",
            ])
            ->selectRaw("COUNT(DISTINCT {$column}) as `objects_count`")
            ->selectRaw(
                "GROUP_CONCAT(DISTINCT {$column} ORDER BY {$column} SEPARATOR ',') as objects_ids",
            )
            ->groupBy('hash')
            ->limit(1000);

        return $query;
    }

    /**
     * @param Builder<Location> $builder
     *
     * @return Builder<Location>
     */
    public function applyBoundariesConditions(Builder $builder): Builder {
        $boundaries = [
            $this->getArgumentSet($this->getResolveInfo()->argumentSet->arguments['locations'] ?? null),
            $this->getBoundaries(),
        ];

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

    public function hasAssetsConditions(): bool {
        return isset($this->getResolveInfo()->argumentSet->arguments['assets']);
    }

    /**
     * @param Builder<Asset> $builder
     *
     * @return Builder<Asset>
     */
    public function applyAssetsConditions(Builder $builder): Builder {
        $arguments = $this->getArgumentSet($this->getResolveInfo()->argumentSet->arguments['assets'] ?? null);
        $builder   = $arguments
            ? $arguments->enhanceBuilder($builder, [])
            : $builder;

        return $builder;
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

    protected function getBoundaries(): ?BoundingBoxInterface {
        $geohashes  = $this->getInput()->boundaries ?? [];
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

    /**
     * @return array<string>
     */
    protected function parseKeys(?string $keys): array {
        // `GROUP_CONCAT()` return truncated string, so we need to filter out
        // truncated (or empty) values.
        return array_values(array_unique(array_filter(explode(',', (string) $keys), static function ($id) {
            return $id && strlen($id) === 36;
        })));
    }
}
