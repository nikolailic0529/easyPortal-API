<?php declare(strict_types = 1);

namespace App\Services\Search\Elastic;

use App\Services\Search\Builders\Builder as SearchBuilder;
use App\Services\Search\Builders\UnionBuilder as SearchCombinedBuilder;
use App\Services\Search\Eloquent\Searchable;
use App\Services\Search\Properties\Value;
use ElasticAdapter\Search\SearchRequest;
use ElasticScoutDriver\Factories\SearchRequestFactory as BaseSearchRequestFactory;
use ElasticScoutDriverPlus\Builders\BoolQueryBuilder;
use ElasticScoutDriverPlus\Builders\SearchRequestBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Laravel\Scout\Builder as ScoutBuilder;
use LogicException;
use stdClass;

use function array_map;
use function count;
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
use function trim;

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
            /** @var SearchBuilder $modelBuilder */
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
        /** @var Model&Searchable $model */
        $model  = $builder->model;
        $query  = parent::makeQuery($builder);
        $string = trim((string) $builder->query);
        $fields = $model->getSearchConfiguration()->getSearchable();

        if (!$fields || $string === '""') {
            $query['bool']['must'] = $this->makeQueryNone($builder, $fields, $string);
        } elseif (!$string || $string === '*') {
            $query['bool']['must'] = $this->makeQueryAll($builder, $fields, $string);
        } elseif (str_starts_with($string, '"') && str_ends_with($string, '"')) {
            $phrase                = mb_substr($string, 1, -1);
            $query['bool']['must'] = $this->makeQueryPhrase($builder, $fields, $phrase);
        } else {
            $words                 = preg_split('/\s+/', $string, -1, PREG_SPLIT_NO_EMPTY);
            $query['bool']['must'] = $this->makeQueryWords($builder, $fields, $words);
        }

        return $query;
    }

    /**
     * @param array<string> $fields
     *
     * @return array<mixed>
     */
    private function makeQueryAll(ScoutBuilder $builder, array $fields, string $string): array {
        return [
            ['match_all' => new stdClass()],
        ];
    }

    /**
     * @param array<string> $fields
     *
     * @return array<mixed>
     */
    private function makeQueryNone(ScoutBuilder $builder, array $fields, string $string): array {
        return [
            ['match_none' => new stdClass()],
        ];
    }

    /**
     * @param array<string> $fields
     *
     * @return array<mixed>
     */
    private function makeQueryPhrase(ScoutBuilder $builder, array $fields, string $string): array {
        $string = "*{$this->escapeWildcardString($string)}*";
        $query  = $this->makeQueryWildcard($builder, $fields, $string);

        return $query;
    }

    /**
     * @param array<string> $fields
     * @param array<string> $words
     *
     * @return array<mixed>
     */
    private function makeQueryWords(ScoutBuilder $builder, array $fields, array $words): array {
        $words  = array_map(fn(string $word): string => $this->escapeWildcardString($word), $words);
        $string = '*'.implode('*', $words).'*';
        $query  = $this->makeQueryWildcard($builder, $fields, $string);

        return $query;
    }

    /**
     * @param array<string> $fields
     *
     * @return array<mixed>
     */
    private function makeQueryWildcard(ScoutBuilder $builder, array $fields, string $string): array {
        $conditions = [];

        foreach ($fields as $field) {
            $conditions[] = [
                'wildcard' => [
                    $field => [
                        'value'            => $string,
                        'case_insensitive' => true,
                    ],
                ],
            ];
        }

        if (count($fields) > 1) {
            $conditions = [
                'bool' => [
                    'should' => $conditions,
                ],
            ];
        }

        return $conditions;
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
                /** @var Model&Searchable $model */
                $model    = $builder->model;
                $name     = key($clause);
                $property = $model->getSearchConfiguration()->getProperty($name);

                if ($property instanceof Value && $property->hasKeyword()) {
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

    protected function escapeWildcardString(string $string): string {
        return preg_replace('/[*?]/', '\\\\$0', $string);
    }
}
