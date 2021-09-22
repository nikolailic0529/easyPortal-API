<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Location;
use App\Services\Organization\CurrentOrganization;
use App\Utils\ModelHelper;
use Illuminate\Database\Eloquent\Builder;

use Illuminate\Database\Eloquent\Relations\HasMany;

use InvalidArgumentException;

use function array_filter;
use function array_values;
use function explode;
use function max;
use function sprintf;
use function strlen;

class Map {
    public function __construct(
        protected CurrentOrganization $organization,
    ) {
        // empty
    }

    /**
     * @param array<string,mixed> $args
     */
    public function __invoke(mixed $root, array $args): Builder {
        $root     = $this->organization->isRoot();
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
            ->when($root, static function (Builder $builder) use ($model, $customer): void {
                // For Root organization we can use values directly from `locations`
                $customerId = "IF(
                    {$model->qualifyColumn('object_type')} = '{$customer->getMorphClass()}',
                    {$model->qualifyColumn('object_id')},
                    NULL)";

                $builder->selectRaw("SUM({$model->qualifyColumn('assets_count')}) as assets_count");
                $builder->selectRaw("COUNT(DISTINCT {$customerId}) AS customers_count");
                $builder->selectRaw("GROUP_CONCAT(DISTINCT {$customerId} SEPARATOR ',') AS customers_ids");
            })
            ->when(!$root, function (Builder $builder) use ($customer): void {
                $asset = new Asset();

                $builder->selectRaw("COUNT(DISTINCT a.{$asset->getKeyName()}) as assets_count");
                $builder->selectRaw("COUNT(DISTINCT c.{$customer->getKeyName()}) as customers_count");
                $builder->selectRaw("GROUP_CONCAT(DISTINCT c.{$customer->getKeyName()}, ',') as customers_ids");
                $this->joinRelation($builder, 'assets', 'a');
                $this->joinRelation($builder, 'customers', 'c');
            })
            ->whereNotNull($model->qualifyColumn('latitude'))
            ->whereNotNull($model->qualifyColumn('longitude'))
            ->groupByRaw("ROUND({$model->qualifyColumn('latitude')} / ?)", [$diff])
            ->groupByRaw("ROUND({$model->qualifyColumn('longitude')} / ?)", [$diff])
            ->orHaving('assets_count', '>', 0)
            ->orHaving('customers_count', '>', 0)
            ->limit(1000);

        return $builder;
    }

    protected function joinRelation(Builder $builder, string $relation, string $alias): Builder {
        $relation = (new ModelHelper($builder))->getRelation($relation);

        if ($relation instanceof HasMany) {
            $builder = $builder->leftJoinSub(
                $relation->getQuery(),
                $alias,
                "{$alias}.{$relation->getForeignKeyName()}",
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
