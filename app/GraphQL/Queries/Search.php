<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Scopes\ContractType;
use App\Models\Scopes\QuoteType;
use ElasticScoutDriver\Factories\SearchRequestFactoryInterface;
use ElasticScoutDriverPlus\Builders\BoolQueryBuilder;
use ElasticScoutDriverPlus\Builders\SearchRequestBuilder;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Collection;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class Search {
    public function __construct(
        protected Gate $gate,
        protected SearchRequestFactoryInterface $factory,
    ) {
        // empty
    }

    /**
     * @param array{search: string} $args
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): Collection {
        // Determine visible models
        /** @var array<class-string<\App\Models\Model&\App\Services\Search\Eloquent\Searchable>,array<\App\Services\Search\Scope>> $models */
        $models = [];

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

        // Nothing?
        if (!$models) {
            return new Collection();
        }

        // Query
        $search = null;
        $boosts = [
            Customer::class => 3,
            Asset::class    => 2,
            Document::class => 1,
        ];

        foreach ($models as $model => $scopes) {
            // Builder
            /** @var \App\Services\Search\Builder $builder */
            $builder = $model::search($args['search']);

            foreach ($scopes as $scope) {
                $builder->applyScope($scope);
            }

            // Join
            if (!$search) {
                $search = new SearchRequestBuilder(new $model(), new BoolQueryBuilder());
            } else {
                $search->join($model);
            }

            // Filters
            /** @var array{query: array{must: array<mixed>, filter: array<mixed>}}|null $query */
            $query             = $this->factory->makeFromBuilder($builder)->toArray()['query']['bool'] ?? null;
            $query['filter'][] = [
                'term' => [
                    '_index' => $builder->index ?: (new $model())->searchableAs(),
                ],
            ];

            $search->should([
                'bool' => $query,
            ]);

            // Boost
            if (isset($boosts[$model])) {
                $search->boostIndex($model, $boosts[$model]);
            }
        }

        // Return
        return $search->size(10)->execute()->models();
    }
}
