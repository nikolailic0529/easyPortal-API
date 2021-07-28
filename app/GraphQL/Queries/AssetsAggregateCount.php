<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\GraphQL\Resolvers\AggregateResolver;
use App\Models\Asset;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as DatabaseBuilder;

class AssetsAggregateCount extends AggregateResolver {

    protected function getQuery(mixed $root): DatabaseBuilder|EloquentBuilder {
        $model = new Asset();
        $query = $model->selectRaw("COUNT(DISTINCT {$model->qualifyColumn($model->getKeyName())}) as count");
        return $query;
    }

    protected function getResult(EloquentBuilder|DatabaseBuilder $builder): mixed {
        $result = $builder->first();
        return $result->count;
    }
}
