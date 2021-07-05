<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\GraphQL\Resolvers\AggregateResolver;
use App\Models\Customer;
use Illuminate\Database\Query\Builder;

class CustomersAggregate extends AggregateResolver {
    protected function getQuery(): Builder {
        $model = new Customer();
        $query = $model->query()
            ->selectRaw("COUNT(DISTINCT {$model->qualifyColumn($model->getKeyName())}) as count")
            ->selectRaw("SUM({$model->qualifyColumn('assets_count')}) as assets")
            ->toBase();

        return $query;
    }
}
