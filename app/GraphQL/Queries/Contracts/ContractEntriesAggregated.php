<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Contracts;

use App\GraphQL\Directives\Directives\Aggregated\BuilderValue;
use App\Models\Document;
use App\Models\ServiceGroup;
use App\Models\ServiceLevel;
use App\Utils\Eloquent\Callbacks\GetKey;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Collection;
use stdClass;

/**
 * @deprecated Please use `groups` query instead.
 */
class ContractEntriesAggregated {
    public function __construct(
        protected Repository $config,
    ) {
        // empty
    }

    /**
     * @param BuilderValue<Document> $root
     *
     * @return array<mixed>
     */
    public function serviceGroups(BuilderValue $root): array {
        $builder = $root->getEloquentBuilder();
        $model   = $builder->getModel();

        /** @var Collection<int, stdClass> $results */
        $results       = $builder
            ->select("{$model->qualifyColumn('service_group_id')} as service_group_id")
            ->selectRaw("COUNT(DISTINCT {$model->qualifyColumn($model->getKeyName())}) as count")
            ->groupBy($model->qualifyColumn('service_group_id'))
            ->having('count', '>', '0')
            ->orderBy('service_group_id')
            ->toBase()
            ->get();
        $serviceGroups = ServiceGroup::query()
            ->whereKey($results->pluck('service_group_id')->all())
            ->get()
            ->keyBy(new GetKey());
        $aggregated    = [];

        foreach ($results as $result) {
            $serviceGroup = $serviceGroups->get($result->service_group_id);
            $aggregated[] = [
                'count'            => (int) $result->count,
                'service_group_id' => $result->service_group_id,
                'serviceGroup'     => $serviceGroup,
            ];
        }

        return $aggregated;
    }

    /**
     * @param BuilderValue<Document> $root
     *
     * @return array<mixed>
     */
    public function serviceLevels(BuilderValue $root): array {
        $builder = $root->getEloquentBuilder();
        $model   = $builder->getModel();

        /** @var Collection<int, stdClass> $results */
        $results       = $builder
            ->select("{$model->qualifyColumn('service_level_id')} as service_level_id")
            ->selectRaw("COUNT(DISTINCT {$model->qualifyColumn($model->getKeyName())}) as count")
            ->groupBy($model->qualifyColumn('service_level_id'))
            ->having('count', '>', '0')
            ->orderBy('service_level_id')
            ->toBase()
            ->get();
        $serviceLevels = ServiceLevel::query()
            ->whereKey($results->pluck('service_level_id')->all())
            ->get()
            ->keyBy(new GetKey());
        $aggregated    = [];

        foreach ($results as $result) {
            $serviceLevel = $serviceLevels->get($result->service_level_id);
            $aggregated[] = [
                'count'            => (int) $result->count,
                'service_level_id' => $result->service_level_id,
                'serviceLevel'     => $serviceLevel,
            ];
        }

        return $aggregated;
    }
}
