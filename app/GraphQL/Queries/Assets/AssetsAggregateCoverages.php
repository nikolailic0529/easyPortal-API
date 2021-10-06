<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Assets;

use App\GraphQL\Resolvers\AggregateResolver;
use App\Models\Asset;
use App\Models\AssetCoverage;
use App\Models\Coverage;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as DatabaseBuilder;

class AssetsAggregateCoverages extends AggregateResolver {

    protected function getQuery(): DatabaseBuilder|EloquentBuilder {
        $model    = new Asset();
        $coverage = new Coverage();
        $pivot    = new AssetCoverage();
        $query    = $model->query()
            ->selectRaw("COUNT(DISTINCT {$model->qualifyColumn($model->getKeyName())}) as assets_count")
            ->selectRaw("{$coverage->getTable()}.*")
            ->join($pivot->getTable(), $model->getQualifiedKeyName(), '=', $pivot->qualifyColumn('asset_id'))
            ->rightJoin(
                $coverage->getTable(),
                $pivot->qualifyColumn('coverage_id'),
                '=',
                $coverage->getQualifiedKeyName(),
            )
            ->groupBy($coverage->getQualifiedKeyName());

        return $query;
    }

    protected function getResult(EloquentBuilder|DatabaseBuilder $builder): mixed {
        $results   = $builder->get();
        $aggregate = [];

        foreach ($results as $result) {
            $aggregate[] = [
                'count'       => $result->assets_count,
                'coverage_id' => $result->getKey(),
                'coverage'    => $result,
            ];
        }

        return $aggregate;
    }
}
