<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Customer;
use App\Models\Location;
use App\Utils\ModelHelper;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use InvalidArgumentException;

use function array_filter;
use function array_values;
use function explode;
use function max;
use function sprintf;
use function strlen;

class Map {
    public function __construct() {
        // empty
    }

    /**
     * @param array<string,mixed> $args
     */
    public function __invoke(mixed $root, array $args): Builder {
        $diff     = max($args['diff'] ?? 0, 0.000000000001);
        $model    = new Location();
        $customer = new Customer();
        $builder  = Location::query()
            ->selectRaw("AVG({$model->qualifyColumn('latitude')}) as latitude_avg")
            ->selectRaw("MIN({$model->qualifyColumn('latitude')}) as latitude_min")
            ->selectRaw("MAX({$model->qualifyColumn('latitude')}) as latitude_max")
            ->selectRaw("AVG({$model->qualifyColumn('longitude')}) as longitude_avg")
            ->selectRaw("MIN({$model->qualifyColumn('longitude')}) as longitude_min")
            ->selectRaw("MAX({$model->qualifyColumn('longitude')}) as longitude_max")
            ->selectRaw('IFNULL(SUM(c.assets_count), 0) as assets_count')
            ->selectRaw("COUNT(DISTINCT c.{$customer->getKeyName()}) as customers_count")
            ->selectRaw("GROUP_CONCAT(DISTINCT c.{$customer->getKeyName()}, ',') as customers_ids")
            ->whereNotNull($model->qualifyColumn('latitude'))
            ->whereNotNull($model->qualifyColumn('longitude'))
            ->groupByRaw("ROUND({$model->qualifyColumn('latitude')} / ?)", [$diff])
            ->groupByRaw("ROUND({$model->qualifyColumn('longitude')} / ?)", [$diff])
            ->orHaving('assets_count', '>', 0)
            ->orHaving('customers_count', '>', 0)
            ->limit(1000);

        $this->joinRelation(
            $builder,
            'customers',
            'c',
            static function (BelongsToMany $relation, Builder $builder): Builder {
                return $builder
                    ->selectRaw($relation->getQualifiedRelatedKeyName())
                    ->selectRaw($relation->getQualifiedForeignPivotKeyName())
                    ->selectRaw($relation->qualifyPivotColumn('assets_count'));
            },
        );

        return $builder;
    }

    protected function joinRelation(Builder $builder, string $relation, string $alias, Closure $callback): Builder {
        $relation = (new ModelHelper($builder))->getRelation($relation);

        if ($relation instanceof BelongsToMany) {
            $builder = $builder->leftJoinSub(
                $callback($relation, $relation->getQuery()),
                $alias,
                "{$alias}.{$relation->getForeignPivotKeyName()}",
                '=',
                $relation->getQualifiedParentKeyName(),
            );
        } else {
            throw new InvalidArgumentException(sprintf(
                'Relation `%s` not supported',
                $relation::class,
            ));
        }

        return $builder;
    }

    /**
     * @return array<string>
     */
    public function customersIds(Location $location): array {
        return array_values(array_filter(explode(',', (string) $location->customers_ids), static function ($id) {
            return strlen($id) === 36;
        }));
    }
}
