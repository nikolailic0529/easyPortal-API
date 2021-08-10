<?php declare(strict_types = 1);

namespace App\Services\Search\Elastic;

use App\Services\Search\Builders\Builder;
use App\Services\Search\Builders\UnionBuilder;
use App\Services\Search\Eloquent\Searchable;
use App\Services\Search\Eloquent\UnionModel;
use App\Services\Search\Properties\Text;
use App\Services\Search\Properties\Uuid;
use App\Services\Search\Scope;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Search\Elastic\SearchRequestFactory
 */
class SearchRequestFactoryTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::makeFilter
     * @covers ::makeSort
     *
     * @dataProvider dataProviderMakeFromBuilder
     *
     * @param array<mixed> $expected
     */
    public function testMakeFromBuilder(array $expected, Closure $prepare): void {
        $factory = $this->app->make(SearchRequestFactory::class);
        $model   = new class() extends Model {
            use Searchable;

            /**
             * @var array<mixed>
             */
            public static array $searchProperties;

            /**
             * @inheritDoc
             */
            public static function getSearchProperties(): array {
                return self::$searchProperties;
            }
        };
        $builder = $this->app->make(Builder::class, [
            'query' => '*',
            'model' => $model,
        ]);

        $model::$searchProperties = (array) $prepare($builder);

        $this->assertEquals($expected, $factory->makeFromBuilder($builder)->toArray());
    }

    /**
     * @covers ::makeFromUnionBuilder
     */
    public function testMakeFromUnionBuilder(): void {
        // Prepare
        $model   = new UnionModel();
        $builder = $this->app->make(UnionBuilder::class, [
            'query' => 'abc',
            'model' => $model,
        ]);
        $a       = new class() extends Model {
            use Searchable;

            /**
             * @inheritDoc
             */
            protected static function getSearchProperties(): array {
                return [];
            }

            public function searchableAs(): string {
                return 'a';
            }
        };
        $b       = new class() extends Model {
            use Searchable;

            /**
             * @inheritDoc
             */
            protected static function getSearchProperties(): array {
                return [];
            }

            public function searchableAs(): string {
                return 'b';
            }
        };
        $scope   = new class() implements Scope {
            public function applyForSearch(Builder $builder, Model $model): void {
                $builder->where('scope', 'test');
            }
        };

        // Build
        $builder->addModel($a::class, [], 2);
        $builder->addModel($b::class, [$scope]);
        $builder->where('test', 'value');

        // Test
        $actual   = $this->app->make(SearchRequestFactory::class)
            ->makeFromUnionBuilder($builder, [
                'perPage' => 10,
                'page'    => 5,
            ])
            ->buildSearchRequest()
            ->toArray();
        $expected = [
            'query'         => [
                'bool' => [
                    'should' => [
                        [
                            'bool' => [
                                'must'   => [
                                    'simple_query_string' => [
                                        'query'  => 'abc',
                                        'flags'  => 'AND|ESCAPE|NOT|OR|PHRASE|PRECEDENCE|WHITESPACE',
                                        'fields' => ['properties.*'],
                                    ],
                                ],
                                'filter' => [
                                    [
                                        'term' => [
                                            '_index' => 'a',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'bool' => [
                                'must'   => [
                                    'simple_query_string' => [
                                        'query'  => 'abc',
                                        'flags'  => 'AND|ESCAPE|NOT|OR|PHRASE|PRECEDENCE|WHITESPACE',
                                        'fields' => ['properties.*'],
                                    ],
                                ],
                                'filter' => [
                                    [
                                        'term' => [
                                            'scope' => 'test',
                                        ],
                                    ],
                                    [
                                        'term' => [
                                            '_index' => 'b',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'filter' => [
                        [
                            [
                                'term' => [
                                    'test' => 'value',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'from'          => 40,
            'size'          => 10,
            'indices_boost' => [
                [
                    'a' => 2.0,
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, mixed>
     */
    public function dataProviderMakeFromBuilder(): array {
        $must = [
            'simple_query_string' => [
                'query'  => '*',
                'flags'  => 'AND|ESCAPE|NOT|OR|PHRASE|PRECEDENCE|WHITESPACE',
                'fields' => [
                    'properties.*',
                ],
            ],
        ];

        return [
            'where'                 => [
                [
                    'query' => [
                        'bool' => [
                            'must'   => $must,
                            'filter' => [
                                [
                                    'term' => ['where' => '123'],
                                ],
                            ],
                        ],
                    ],
                ],
                static function (Builder $builder): void {
                    $builder->where('where', '123');
                },
            ],
            'whereIn'               => [
                [
                    'query' => [
                        'bool' => [
                            'must'   => $must,
                            'filter' => [
                                [
                                    'terms' => ['whereIn' => ['a', 'b', 'c']],
                                ],
                                [
                                    'terms' => ['whereIn2' => ['1', '2', '3']],
                                ],
                            ],
                        ],
                    ],
                ],
                static function (Builder $builder): void {
                    $builder->whereIn('whereIn', ['a', 'b', 'c']);
                    $builder->whereIn('whereIn2', ['1', '2', '3']);
                },
            ],
            'whereNot'              => [
                [
                    'query' => [
                        'bool' => [
                            'must'   => $must,
                            'filter' => [
                                [
                                    'bool' => [
                                        'must_not' => [
                                            [
                                                'term' => ['whereNot' => '123'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                static function (Builder $builder): void {
                    $builder->whereNot('whereNot', '123');
                },
            ],
            'where + whereNot'      => [
                [
                    'query' => [
                        'bool' => [
                            'must'   => $must,
                            'filter' => [
                                [
                                    'term' => ['where' => '123'],
                                ],
                                [
                                    'bool' => [
                                        'must_not' => [
                                            [
                                                'term' => ['whereNot' => '123'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                static function (Builder $builder): void {
                    $builder->where('where', '123');
                    $builder->whereNot('whereNot', '123');
                },
            ],
            'whereNotIn'            => [
                [
                    'query' => [
                        'bool' => [
                            'must'   => $must,
                            'filter' => [
                                [
                                    'bool' => [
                                        'must_not' => [
                                            [
                                                'terms' => ['whereNotIn' => ['a', 'b', 'c']],
                                            ],
                                            [
                                                'terms' => ['whereNotIn2' => ['1', '2', '3']],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                static function (Builder $builder): void {
                    $builder->whereNotIn('whereNotIn', ['a', 'b', 'c']);
                    $builder->whereNotIn('whereNotIn2', ['1', '2', '3']);
                },
            ],
            'whereNot + whereNotIn' => [
                [
                    'query' => [
                        'bool' => [
                            'must'   => $must,
                            'filter' => [
                                [
                                    'bool' => [
                                        'must_not' => [
                                            [
                                                'term' => ['whereNot' => '123'],
                                            ],
                                            [
                                                'terms' => ['whereNotIn' => ['a', 'b', 'c']],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                static function (Builder $builder): void {
                    $builder->whereNot('whereNot', '123');
                    $builder->whereNotIn('whereNotIn', ['a', 'b', 'c']);
                },
            ],
            'order'                 => [
                [
                    'query' => [
                        'bool' => [
                            'must' => $must,
                        ],
                    ],
                    'sort'  => [
                        [
                            'key.a' => [
                                'order'         => 'asc',
                                'unmapped_type' => 'keyword',
                            ],
                        ],
                        [
                            'key.b.keyword' => [
                                'order'         => 'desc',
                                'unmapped_type' => 'keyword',
                            ],
                        ],
                    ],
                ],
                static function (Builder $builder): array {
                    $builder->orderBy('key.a', 'asc');
                    $builder->orderBy('key.b', 'desc');

                    return [
                        'key' => [
                            'a' => new Uuid('a', true),
                            'b' => new Text('b', true),
                        ],
                    ];
                },
            ],
        ];
    }
    //</editor-fold>
}
