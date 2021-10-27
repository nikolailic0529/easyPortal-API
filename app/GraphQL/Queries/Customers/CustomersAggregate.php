<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Customers;

use App\GraphQL\Resolvers\AggregateResolver;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as DatabaseBuilder;

class CustomersAggregate extends AggregateResolver {
    protected function getQuery(): DatabaseBuilder|EloquentBuilder {
        $model = new Customer();
        $query = $model->query()
            ->selectRaw("COUNT(DISTINCT {$model->qualifyColumn($model->getKeyName())}) as count")
            ->selectRaw("IFNULL(SUM({$model->qualifyColumn('assets_count')}), 0) as assets");

        return $query;
    }
}