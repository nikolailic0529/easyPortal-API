<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Contracts;

use App\GraphQL\Directives\Directives\Aggregated\BuilderValue;

class ContractsAggregated {
    /**
     * @return array<mixed>
     */
    public function prices(BuilderValue $root): array {
        $builder    = $root->getEloquentBuilder();
        $model      = $builder->getModel();
        $results    = $builder
            ->select("{$model->qualifyColumn('currency_id')} as currency_id")
            ->selectRaw("COUNT(DISTINCT {$model->qualifyColumn($model->getKeyName())}) as count")
            ->selectRaw("IFNULL(SUM({$model->qualifyColumn('price')}), 0) as amount")
            ->groupBy($model->qualifyColumn('currency_id'))
            ->with('currency')
            ->get();
        $aggregated = [];

        foreach ($results as $result) {
            $result->amount = (float) $result->amount;
            $aggregated[]   = $result;
        }

        return $aggregated;
    }
}