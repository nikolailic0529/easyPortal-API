<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Location;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\GraphQL\Helpers\ModelHelper;

use function max;
use function sprintf;

class Map {
    /**
     * @param array<string,mixed> $args
     */
    public function __invoke(mixed $root, array $args): Builder {
        $diff     = max($args['diff'] ?? 0, 0.000000000001);
        $model    = new Location();
        $asset    = new Asset();
        $customer = new Customer();
        $builder  = Location::query()
            ->selectRaw("AVG({$model->qualifyColumn('latitude')}) as latitude")
            ->selectRaw("AVG({$model->qualifyColumn('longitude')}) as longitude")
            ->selectRaw("COUNT(DISTINCT a.{$asset->getKeyName()}) as assets")
            ->selectRaw("COUNT(DISTINCT c.{$customer->getKeyName()}) as customers")
            ->whereNotNull($model->qualifyColumn('latitude'))
            ->whereNotNull($model->qualifyColumn('longitude'))
            ->groupByRaw("ROUND({$model->qualifyColumn('latitude')} / ?)", [$diff])
            ->groupByRaw("ROUND({$model->qualifyColumn('longitude')} / ?)", [$diff])
            ->orHaving('assets', '>', 0)
            ->orHaving('customers', '>', 0)
            ->limit(1000);
        $builder  = $this->joinRelation($builder, 'assets', 'a');
        $builder  = $this->joinRelation($builder, 'customers', 'c');

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
}
