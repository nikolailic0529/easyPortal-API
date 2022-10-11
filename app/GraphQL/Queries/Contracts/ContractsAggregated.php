<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Contracts;

use App\GraphQL\Directives\Directives\Aggregated\BuilderValue;
use App\Models\Data\Currency;
use App\Models\Document;
use App\Utils\Eloquent\Callbacks\GetKey;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Collection;
use stdClass;

class ContractsAggregated {
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
    public function prices(BuilderValue $root): array {
        $builder = $root->getEloquentBuilder();
        $model   = $builder->getModel();

        /** @var Collection<int, stdClass> $results */
        $results    = $builder
            ->select("{$model->qualifyColumn('currency_id')} as currency_id")
            ->selectRaw("COUNT(DISTINCT {$model->qualifyColumn($model->getKeyName())}) as count")
            ->selectRaw("SUM(IFNULL({$model->qualifyColumn('price')}, 0)) as amount")
            ->groupBy($model->qualifyColumn('currency_id'))
            ->having('count', '>', '0')
            ->orderBy('currency_id')
            ->toBase()
            ->get();
        $currencies = Currency::query()
            ->whereKey($results->pluck('currency_id')->all())
            ->get()
            ->keyBy(new GetKey());
        $aggregated = [];

        foreach ($results as $result) {
            $currency     = $currencies->get($result->currency_id);
            $aggregated[] = [
                'count'       => (int) $result->count,
                'amount'      => (float) $result->amount,
                'currency_id' => $result->currency_id,
                'currency'    => $currency,
            ];
        }

        return $aggregated;
    }
}
