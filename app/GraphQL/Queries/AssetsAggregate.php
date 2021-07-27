<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\GraphQL\Resolvers\AggregateResolver;
use App\Models\Asset;
use App\Models\Coverage;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as DatabaseBuilder;
use Illuminate\Support\Collection;

class AssetsAggregate extends AggregateResolver {
    protected function getQuery(): DatabaseBuilder|EloquentBuilder {
        $model = new Asset();
        $query = $model->query()
            ->select([$model->getKeyName(), $model->qualifyColumn('type_id')])
            ->with('type')
            ->with('coverages', static function ($query) {
                return $query->withCount('assets');
            });

        return $query;
    }

    protected function getResult(EloquentBuilder|DatabaseBuilder $builder): mixed {
        $results   = $builder->get();
        $aggregate = [
            'count'     => $results->count(),
            'types'     => [],
            'coverages' => [],
        ];

        $types = $results->groupBy('type_id')->map(static function ($group, $key) {
            return [
                'count'   => $group->count(),
                'type_id' => $key,
                'type'    => $group->first()->type,
            ];
        });

        $coverages = new Collection();
        $results->each(static function ($result) use ($coverages): void {
            $result->coverages->each(static function ($coverage) use ($coverages): void {
                $coverages->push($coverage);
            });
        });

        $coverages = $coverages
            ->groupBy((new Coverage())->getKeyName())
            ->map(static function ($group, $key): array {
                $coverage = $group->first();
                return [
                    'count'       => $coverage->assets_count,
                    'coverage_id' => $key,
                    'coverage'    => $coverage,
                ];
            });

        $aggregate['types']     = $types;
        $aggregate['coverages'] = $coverages;

        return $aggregate;
    }
}
