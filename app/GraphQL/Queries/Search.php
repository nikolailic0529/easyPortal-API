<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Scopes\DocumentIsContractScope;
use App\Models\Scopes\DocumentIsQuoteScope;
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

    /**
     * @return Builder<UnionModel>
     */
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

        if ($this->gate->check(['assets-view'])) {
            $models[Asset::class] = [];
        }

        if ($this->gate->check(['customers-view'])) {
            $models[Customer::class] = [];
        }

        if ($this->gate->check(['contracts-view', 'quotes-view'])) {
            $models[Document::class] = [];
        } elseif ($this->gate->check(['contracts-view'])) {
            $models[Document::class] = [DocumentIsContractScope::class];
        } elseif ($this->gate->check(['quotes-view'])) {
            $models[Document::class] = [DocumentIsQuoteScope::class];
        } else {
            // empty
        }

        // Add
        foreach ($models as $model => $scopes) {
            $builder->addModel($model, $scopes, $boosts[$model]);
        }

        // Return
        return $builder;
    }
}
