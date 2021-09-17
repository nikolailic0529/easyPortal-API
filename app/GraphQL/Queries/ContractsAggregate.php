<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\GraphQL\Resolvers\AggregateResolver;
use App\Models\Document;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as DatabaseBuilder;

class ContractsAggregate extends AggregateResolver {
    public function __construct(
        protected ContractTypes $types,
    ) {
        // empty
    }

    protected function getQuery(): DatabaseBuilder|EloquentBuilder {
        $model = new Document();
        $query = $model->query()
            ->queryContracts()
            ->select("{$model->qualifyColumn('currency_id')} as currency_id")
            ->selectRaw("COUNT(DISTINCT {$model->qualifyColumn($model->getKeyName())}) as count")
            ->selectRaw("IFNULL(SUM({$model->qualifyColumn('price')}), 0) as amount")
            ->groupBy($model->qualifyColumn('currency_id'))
            ->with('currency');

        return $query;
    }

    protected function getResult(EloquentBuilder|DatabaseBuilder $builder): mixed {
        $results   = $builder->get();
        $aggregate = [
            'count'  => 0,
            'prices' => [],
        ];

        foreach ($results as $result) {
            $result->amount        = (float) $result->amount;
            $aggregate['count']   += (int) $result->count;
            $aggregate['prices'][] = $result;
        }

        return $aggregate;
    }
}
