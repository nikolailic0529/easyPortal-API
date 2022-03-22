<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Assets;

use App\GraphQL\Directives\Directives\Aggregated\BuilderValue;
use App\Models\Asset;
use App\Models\Coverage;
use App\Models\Customer;
use App\Models\Type;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Expression;
use InvalidArgumentException;
use stdClass;

use function sprintf;

class AssetsAggregated {
    /**
     * @return array<mixed>
     */
    public function types(BuilderValue $root): array {
        $builder = $root->getEloquentBuilder();
        $model   = $builder->getModel();

        if (!($model instanceof Asset)) {
            throw new InvalidArgumentException(sprintf(
                'Expected `%s` model, `%s` given.',
                Customer::class,
                $model::class,
            ));
        }

        $key        = $model->type()->getForeignKeyName();
        $results    = $builder
            ->select($key, new Expression('COUNT(*) as `count`'))
            ->whereNotNull($key)
            ->groupBy($key)
            ->having('count', '>', 0)
            ->toBase()
            ->get()
            ->keyBy($key)
            ->sortKeys();
        $types      = Type::query()
            ->whereKey($results->keys()->all())
            ->get();
        $aggregated = [];

        foreach ($types as $type) {
            /** @var stdClass $result */
            $result       = $results->get($type->getKey());
            $aggregated[] = [
                'count'   => (int) $result->count,
                'type_id' => $type->getKey(),
                'type'    => $type,
            ];
        }

        return $aggregated;
    }

    /**
     * @return array<mixed>
     */
    public function coverages(BuilderValue $root): array {
        $builder = $root->getEloquentBuilder();
        $model   = $builder->getModel();

        if (!($model instanceof Asset)) {
            throw new InvalidArgumentException(sprintf(
                'Expected `%s` model, `%s` given.',
                Customer::class,
                $model::class,
            ));
        }

        $coverage   = new Coverage();
        $results    = $coverage::query()
            ->select([
                new Expression($coverage->qualifyColumn('*')),
                new Expression('SUM(a.`count`) as assets_count'),
            ])
            ->joinRelation(
                'assets',
                'a',
                static function (BelongsToMany $relation, Builder $query) use ($builder): Builder {
                    return $query
                        ->select([
                            new Expression("COUNT({$query->getModel()->getQualifiedKeyName()}) as `count`"),
                            new Expression($relation->getQualifiedForeignPivotKeyName()),
                        ])
                        ->mergeConstraintsFrom($builder)
                        ->groupBy($relation->getQualifiedForeignPivotKeyName());
                },
            )
            ->groupBy($coverage->getQualifiedKeyName())
            ->having('assets_count', '>', 0)
            ->orderBy($coverage->getQualifiedKeyName())
            ->get();
        $aggregated = [];

        foreach ($results as $result) {
            /** @var Coverage $result */
            $aggregated[] = [
                'count'       => $result->getAttribute('assets_count'),
                'coverage_id' => $result->getKey(),
                'coverage'    => $result,
            ];
        }

        return $aggregated;
    }
}
