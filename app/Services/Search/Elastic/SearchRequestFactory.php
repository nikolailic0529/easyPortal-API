<?php declare(strict_types = 1);

namespace App\Services\Search\Elastic;

use App\Services\Search\Builders\Builder as SearchBuilder;
use App\Services\Search\Builders\UnionBuilder as SearchCombinedBuilder;
use ElasticScoutDriver\Factories\SearchRequestFactory as BaseSearchRequestFactory;
use ElasticScoutDriverPlus\Builders\BoolQueryBuilder;
use ElasticScoutDriverPlus\Builders\SearchRequestBuilder;
use Illuminate\Support\Collection;
use Laravel\Scout\Builder;
use Laravel\Scout\Builder as ScoutBuilder;
use LogicException;

use function is_array;
use function key;
use function reset;
use function sprintf;

class SearchRequestFactory extends BaseSearchRequestFactory {
    /**
     * @param array<mixed> $options
     */
    public function makeFromUnionBuilder(
        SearchCombinedBuilder $builder,
        array $options = [],
    ): SearchRequestBuilder {
        // Models
        $search = null;

        foreach ($builder->getModels() as $model => $settings) {
            // Builder
            /** @var \App\Services\Search\Builders\Builder $modelBuilder */
            $modelBuilder = $model::search($builder->query);

            foreach ($settings['scopes'] as $scope) {
                $modelBuilder->applyScope($scope);
            }

            // Join
            if (!$search) {
                $search = new SearchRequestBuilder(new $model(), new BoolQueryBuilder());
            } else {
                $search->join($model);
            }

            // Filters
            /** @var array{must: array<mixed>, filter: array<mixed>}|null $query */
            $query             = $this->makeQuery($modelBuilder)['bool'] ?? null;
            $query['filter'][] = [
                'term' => [
                    '_index' => $modelBuilder->index ?: (new $model())->searchableAs(),
                ],
            ];

            $search->should([
                'bool' => $query,
            ]);

            // Boost
            if (isset($settings['boost'])) {
                $search->boostIndex($model, $settings['boost']);
            }
        }

        if (!$search) {
            throw new LogicException(sprintf(
                'Failed to create `%s` instance. No models?',
                SearchRequestBuilder::class,
            ));
        }

        // Global Filter
        $filter = $this->makeFilter($builder) ?? null;

        if ($filter) {
            $search->filter($filter);
        }

        // Sort
        $sort = $this->makeSort($builder);

        if ($sort) {
            $search->sortRaw($sort);
        }

        // From/Size
        $from = $this->makeFrom($options);
        $size = $this->makeSize($builder, $options);

        if ($from) {
            $search->from($from);
        }

        if ($size) {
            $search->size($size);
        }

        // Return
        return $search;
    }

    /**
     * @inheritDoc
     */
    protected function makeQuery(Builder $builder): array {
        // Default `query_string` is not safe for end-user: it will return an
        // error for invalid queries, thus it should be escaped. So we replace
        // it with `simple_query_string` that doesn't have these problems.
        //
        // https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-simple-query-string-query.html#simple-query-string-syntax
        $query = parent::makeQuery($builder);

        if (isset($query['bool']['must']['query_string'])) {
            /** @var \Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable $model */
            $model                                        = $builder->model;
            $query['bool']['must']['simple_query_string'] = [
                'query'  => $query['bool']['must']['query_string']['query'],
                'flags'  => 'AND|ESCAPE|NOT|OR|PHRASE|PRECEDENCE|WHITESPACE',
                'fields' => $model->getSearchConfiguration()->getSearchable(),
            ];

            unset($query['bool']['must']['query_string']);
        }

        return $query;
    }

    /**
     * @return array<mixed>|null
     */
    protected function makeFilter(ScoutBuilder $builder): ?array {
        $filter = new Collection(parent::makeFilter($builder));

        if ($builder instanceof SearchBuilder) {
            $not = new Collection();

            if ($builder->whereNots) {
                $not = $not->merge($this->getTerms($builder->whereNots));
            }

            if ($builder->whereNotIns) {
                $not = $not->merge($this->getTerms($builder->whereNotIns));
            }

            if (!$not->isEmpty()) {
                $filter = $filter->merge([
                    [
                        'bool' => [
                            'must_not' => $not->all(),
                        ],
                    ],
                ]);
            }
        }

        return $filter->isNotEmpty() ? $filter->all() : null;
    }

    /**
     * @param array<string,array<mixed>|mixed> $where
     */
    private function getTerms(array $where): Collection {
        return (new Collection($where))
            ->map(static function (mixed $value, string $field) {
                return is_array($value)
                    ? ['terms' => [$field => $value]]
                    : ['term' => [$field => $value]];
            })
            ->values();
    }

    /**
     * @return array<array<string, mixed>>|null
     */
    protected function makeSort(ScoutBuilder $builder): ?array {
        $sort = (new Collection(parent::makeSort($builder)))
            ->map(static function (array $clause) use ($builder): array {
                /** @var \Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable $model */
                $model    = $builder->model;
                $name     = key($clause);
                $property = $model->getSearchConfiguration()->getProperty($name);

                if ($property?->hasKeyword()) {
                    $name = "{$name}.keyword";
                }

                return [
                    $name => [
                        'order'         => reset($clause),
                        'unmapped_type' => 'keyword',
                    ],
                ];
            })
            ->all();

        return $sort ?: null;
    }
}
