<?php declare(strict_types = 1);

namespace App\Services\Search\Elastic;

use App\Services\Search\Builders\Builder as SearchBuilder;
use App\Services\Search\Builders\UnionBuilder as SearchCombinedBuilder;
use ElasticAdapter\Search\SearchRequest;
use ElasticScoutDriver\Factories\SearchRequestFactory as BaseSearchRequestFactory;
use ElasticScoutDriverPlus\Builders\BoolQueryBuilder;
use ElasticScoutDriverPlus\Builders\SearchRequestBuilder;
use Illuminate\Support\Collection;
use Laravel\Scout\Builder as ScoutBuilder;
use LogicException;

use function array_map;
use function implode;
use function is_array;
use function key;
use function mb_substr;
use function preg_replace;
use function preg_split;
use function reset;
use function sprintf;
use function str_ends_with;
use function str_starts_with;

use const PREG_SPLIT_NO_EMPTY;

class SearchRequestFactory extends BaseSearchRequestFactory {
    /**
     * @inheritDoc
     */
    public function makeFromBuilder(ScoutBuilder $builder, array $options = []): SearchRequest {
        $request = parent::makeFromBuilder($builder, $options);
        $from    = $this->makeOffset($builder, $options);

        if ($from) {
            $request->from($from);
        }

        return $request;
    }

    /**
     * @param array<mixed> $options
     */
    public function makeFromUnionBuilder(
        SearchCombinedBuilder $builder,
        array $options = [],
    ): SearchRequestBuilder {
        // Query
        $query = new BoolQueryBuilder();

        foreach ($builder->getModels() as $model => $settings) {
            // Builder
            /** @var \App\Services\Search\Builders\Builder $modelBuilder */
            $modelBuilder = $model::search($builder->query);

            foreach ($settings['scopes'] as $scope) {
                $modelBuilder->applyScope($scope);
            }

            // Filters
            /** @var array{must: array<mixed>, filter: array<mixed>}|null $modelQuery */
            $modelQuery             = $this->makeQuery($modelBuilder)['bool'] ?? null;
            $modelQuery['filter'][] = [
                'term' => [
                    '_index' => $modelBuilder->index ?: (new $model())->searchableAs(),
                ],
            ];

            $query->should([
                'bool' => $modelQuery,
            ]);
        }

        // Global Filter
        $filter = $this->makeFilter($builder) ?? null;

        if ($filter) {
            $query->filter($filter);
        }

        // Join & Settings
        $search = null;

        foreach ($builder->getModels() as $model => $settings) {
            // Join
            if (!$search) {
                $search = new SearchRequestBuilder($query, new $model());
            } else {
                $search->join($model);
            }

            // Boost
            if (isset($settings['boost'])) {
                $search->boostIndex($model, $settings['boost']);
            }
        }

        // Empty?
        if (!$search) {
            throw new LogicException(sprintf(
                'Failed to create `%s` instance. No models?',
                SearchRequestBuilder::class,
            ));
        }

        // Sort
        $sort = $this->makeSort($builder);

        if ($sort) {
            $search->sortRaw($sort);
        }

        // From/Size
        $from = $this->makeOffset($builder, $options);
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
    protected function makeQuery(ScoutBuilder $builder): array {
        $query = parent::makeQuery($builder);

        if (isset($query['bool']['must']['query_string'])) {
            /** @var \Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable $model */
            $model                                 = $builder->model;
            $query['bool']['must']['query_string'] = [
                'query'            => $this->prepareQueryString($query['bool']['must']['query_string']['query']),
                'fields'           => $model->getSearchConfiguration()->getSearchable(),
                'default_operator' => 'AND',
                'analyze_wildcard' => true,
            ];
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

    /**
     * @param array<mixed> $options
     */
    protected function makeOffset(ScoutBuilder $builder, array $options): ?int {
        $offset = null;

        if ($builder instanceof SearchBuilder) {
            $offset = $builder->offset;
        }

        return $this->makeFrom($options) ?? $offset;
    }

    protected function prepareQueryString(string $string): string {
        if (str_starts_with($string, '"') && str_ends_with($string, '"')) {
            // Exact phrase
            $string = '"'.mb_substr($this->escapeQueryString($string), 2, -2).'"';
        } elseif ($string === '*') {
            // as is
        } else {
            // Wildcard words
            $words  = preg_split('/\s+/', $string, -1, PREG_SPLIT_NO_EMPTY);
            $words  = array_map(function (string $word): string {
                return "*{$this->escapeQueryString($word)}*";
            }, $words);
            $string = implode(' ', $words);
        }

        return $string;
    }

    protected function escapeQueryString(string $string): string {
        // https://github.com/elastic/elasticsearch-php/issues/620#issuecomment-901727162
        return preg_replace(
            [
                '_[<>]+_',
                '_[-+=!(){}[\]^"~*?:\\/\\\\]|&(?=&)|\|(?=\|)_',
            ],
            [
                '',
                '\\\\$0',
            ],
            $string,
        );
    }
}
