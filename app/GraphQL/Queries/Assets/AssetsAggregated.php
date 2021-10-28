<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Assets;

use App\GraphQL\Directives\Directives\Aggregated\BuilderValue;
use App\Models\Asset;
use App\Models\Callbacks\GetKey;
use App\Models\Coverage;
use App\Models\Customer;
use App\Models\Type;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Expression;
use InvalidArgumentException;

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
            ->get();
        $types      = Type::query()
            ->whereKey($results->pluck($key)->all())
            ->get()
            ->keyBy(new GetKey());
        $aggregated = [];

        foreach ($results as $result) {
            /** @var \stdClass $result */
            $aggregated[] = [
                'count'   => $result->count,
                'type_id' => $result->{$key},
                'type'    => $types->get($result->{$key}),
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
        $results    = $builder
            ->selectRaw("c.{$model->coverages()->getRelatedPivotKeyName()} as `coverage_id`")
            ->selectRaw("COUNT({$model->getQualifiedKeyName()}) as `count`")
            ->joinRelation('coverages', 'c', static function (BelongsToMany $relation, Builder $builder): Builder {
                return $builder->select(
                    $relation->getQualifiedForeignPivotKeyName(),
                    $relation->getQualifiedRelatedPivotKeyName(),
                );
            })
            ->groupBy('coverage_id')
            ->having('count', '>', 0)
            ->toBase()
            ->get();
        $coverages  = $coverage::query()
            ->whereKey($results->pluck('coverage_id')->all())
            ->get()
            ->keyBy(new GetKey());
        $aggregated = [];

        foreach ($results as $result) {
            /** @var \stdClass $result */
            $aggregated[] = [
                'count'       => $result->count,
                'coverage_id' => $result->coverage_id,
                'coverage'    => $coverages->get($result->coverage_id),
            ];
        }

        return $aggregated;
    }
}
