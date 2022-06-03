<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Contracts;

use App\GraphQL\Directives\Directives\Aggregated\BuilderValue;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

use function reset;

class ContractsAggregated {
    public function __construct(
        protected Repository $config,
    ) {
        // empty
    }

    /**
     * @param BuilderValue<Model> $root
     *
     * @return array<mixed>
     */
    public function prices(BuilderValue $root): array {
        $statuses = (array) $this->config->get('ep.document_statuses_no_price');
        $builder  = $root->getEloquentBuilder();
        $model    = $builder->getModel();
        $query    = '0';
        $bindings = [];

        if ($statuses) {
            $has   = $model->newQueryWithoutScopes()
                ->whereHas('statuses', static function (Builder $builder) use ($statuses): void {
                    $builder->whereIn($builder->getModel()->getQualifiedKeyName(), $statuses);
                })
                ->toBase();
            $where = reset($has->wheres)['query'] ?? null;

            if ($where instanceof QueryBuilder) {
                $query    = "EXISTS({$where->toSql()})";
                $bindings = $where->getBindings();
            }
        }

        return $builder
            ->select("{$model->qualifyColumn('currency_id')} as currency_id")
            ->selectRaw("COUNT(DISTINCT {$model->qualifyColumn($model->getKeyName())}) as count")
            ->selectRaw("SUM(IF({$query}, 0, IFNULL({$model->qualifyColumn('price')}, 0))) as amount", $bindings)
            ->groupBy($model->qualifyColumn('currency_id'))
            ->with('currency')
            ->get()
            ->all();
    }
}
