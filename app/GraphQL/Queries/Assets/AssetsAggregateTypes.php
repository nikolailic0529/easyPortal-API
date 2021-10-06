<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Assets;

use App\GraphQL\Resolvers\AggregateResolver;
use App\Models\Asset;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as DatabaseBuilder;

class AssetsAggregateTypes extends AggregateResolver {

    protected function getQuery(): DatabaseBuilder|EloquentBuilder {
        $model = new Asset();
        $query = $model->query()
            ->select("{$model->qualifyColumn('type_id')} as type_id")
            ->selectRaw("COUNT(DISTINCT {$model->qualifyColumn($model->getKeyName())}) as count")
            ->groupBy($model->qualifyColumn('type_id'))
            ->with('type');

        return $query;
    }

    protected function getResult(EloquentBuilder|DatabaseBuilder $builder): mixed {
        $results   = $builder->get();
        $aggregate = [];

        foreach ($results as $result) {
            $aggregate[] = [
                'count'   => $result->count,
                'type_id' => $result->type_id,
                'type'    => $result->type,
            ];
        }

        return $aggregate;
    }
}
