<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Scopes\ContractType;
use App\Models\Scopes\QuoteType;
use App\Services\Search\Builders\UnionBuilder;
use App\Services\Search\Eloquent\UnionModel;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Builder;

class Search {
    public function __construct(
        protected Gate $gate,
    ) {
        // empty
    }

    public function builder(): Builder {
        return UnionModel::query();
    }

    public function __invoke(UnionBuilder $builder): UnionBuilder {
        // Models
        $models = [];
        $boosts = [
            Customer::class => 3,
            Asset::class    => 2,
            Document::class => 1,
        ];

        if ($this->gate->any(['assets-view', 'customers-view'])) {
            $models[Asset::class] = [];
        }

        if ($this->gate->any(['customers-view'])) {
            $models[Customer::class] = [];
        }

        if ($this->gate->any(['customers-view']) || $this->gate->check(['contracts-view', 'quotes-view'])) {
            $models[Document::class] = [];
        } elseif ($this->gate->check(['contracts-view'])) {
            $models[Document::class] = [ContractType::class];
        } elseif ($this->gate->check(['quotes-view'])) {
            $models[Document::class] = [QuoteType::class];
        } else {
            // empty
        }

        // Add
        foreach ($models as $model => $scopes) {
            $builder->addModel($model, $scopes, $boosts[$model] ?? null);
        }

        // Return
        return $builder;
    }
}
