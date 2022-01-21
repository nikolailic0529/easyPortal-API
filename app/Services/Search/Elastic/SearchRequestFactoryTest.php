<?php declare(strict_types = 1);

namespace App\Services\Search\Elastic;

use App\Services\Search\Builders\Builder;
use App\Services\Search\Builders\UnionBuilder;
use App\Services\Search\Configuration;
use App\Services\Search\Eloquent\Searchable;
use App\Services\Search\Eloquent\UnionModel;
use App\Services\Search\Properties\Property;
use App\Services\Search\Properties\Text;
use App\Services\Search\Properties\Uuid;
use App\Services\Search\Properties\Value;
use App\Services\Search\Scope;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Builder as ScoutBuilder;
use stdClass;
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
            protected static function getSearchProperties(): array {
                return self::$searchProperties;
            }
        };
        $builder = $this->app->make(Builder::class, [
            'query' => '*',
            'model' => $model,
        ]);

        $model::$searchProperties = (array) $prepare($builder) ?: ['a' => new Text('a', true)];

        $this->assertEquals($expected, $factory->makeFromBuilder($builder)->toArray());
    }

    /**
     * @covers ::makeFromUnionBuilder
     */
    public function testMakeFromUnionBuilder(): void {
        // Prepare
        $model   = new UnionModel();
        $builder = $this->app->make(UnionBuilder::class, [
            'query' => 'a[b]c',
            'model' => $model,
        ]);
        $a       = new class() extends Model {
            use Searchable;

            /**
             * @inheritDoc
             */
            protected static function getSearchProperties(): array {
                return ['a' => new Text('a', true)];
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
                return ['a' => new Text('a', true)];
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
                                    [
                                        'wildcard' => [
                                            Configuration::getPropertyName('a') => [
                                                'value'            => '*a[b]c*',
                                                'case_insensitive' => true,
                                            ],
                                        ],
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
                                    [
                                        'wildcard' => [
                                            Configuration::getPropertyName('a') => [
                                                'value'            => '*a[b]c*',
                                                'case_insensitive' => true,
                                            ],
                                        ],
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

    /**
     * @covers ::makeFromUnionBuilder
     */
    public function testMakeFromUnionBuilderLimitOffset(): void {
        // Prepare
        $model   = new UnionModel();
        $builder = $this->app->make(UnionBuilder::class, [
            'query' => 'a[b]c',
            'model' => $model,
        ]);
        $a       = new class() extends Model {
            use Searchable;

            /**
             * @inheritDoc
             */
            protected static function getSearchProperties(): array {
                return ['a' => new Text('a', true)];
            }

            public function searchableAs(): string {
                return 'a';
            }
        };

        // Build
        $builder->addModel($a::class, []);
        $builder->take(123);
        $builder->offset(45);

        // Test
        $actual   = $this->app->make(SearchRequestFactory::class)
            ->makeFromUnionBuilder($builder)
            ->buildSearchRequest()
            ->toArray();
        $expected = [
            'query' => [
                'bool' => [
                    'should' => [
                        [
                            'bool' => [
                                'must'   => [
                                    [
                                        'wildcard' => [
                                            Configuration::getPropertyName('a') => [
                                                'value'            => '*a[b]c*',
                                                'case_insensitive' => true,
                                            ],
                                        ],
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
                    ],
                ],
            ],
            'from'  => 45,
            'size'  => 123,
        ];

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::makeQuery
     *
     * @dataProvider dataProviderEscapeQueryString
     *
     * @param array<mixed> $expected
     * @param array<mixed> $properties
     */
    public function testMakeQuery(array $expected, array $properties, string $query): void {
        $model   = new class() extends Model {
            use Searchable;

            /**
             * @var array<mixed>
             */
            public static array $properties = [];

            /**
             * @inheritDoc
             */
            protected static function getSearchProperties(): array {
                return self::$properties;
            }
        };
        $builder = new ScoutBuilder($model, $query);
        $factory = new class() extends SearchRequestFactory {
            public function __construct() {
                // empty
            }

            /**
             * @inheritDoc
             */
            public function makeQuery(ScoutBuilder $builder): array {
                return parent::makeQuery($builder);
            }
        };

        $model::$properties = $properties;
        $actual             = $factory->makeQuery($builder);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::escapeQueryString
     */
    public function testEscapeQueryString(): void {
        $this->assertEquals(
            '\\"te\\-xt \\(with\\)\\! \\{special\\} \\* \\&& \\/characters\\?\\\\\\"',
            (new class() extends SearchRequestFactory {
                public function __construct() {
                    // empty
                }

                public function escapeQueryString(string $string): string {
                    return parent::escapeQueryString($string);
                }
            })->escapeQueryString('"<te-xt>>> (with)! {special} * && /characters?\\"'),
        );
    }

    /**
     * @covers ::escapeWildcardString
     */
    public function testEscapeWildcardString(): void {
        $this->assertEquals(
            'text with \\* and \\? and \\ characters.',
            (new class() extends SearchRequestFactory {
                public function __construct() {
                    // empty
                }

                public function escapeWildcardString(string $string): string {
                    return parent::escapeWildcardString($string);
                }
            })->escapeWildcardString('text with * and ? and \\ characters.'),
        );
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, mixed>
     */
    public function dataProviderMakeFromBuilder(): array {
        $must = [
            [
                'match_all' => new stdClass(),
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
                            Configuration::getPropertyName('key.a') => [
                                'order'         => 'asc',
                                'unmapped_type' => 'keyword',
                            ],
                        ],
                        [
                            Configuration::getPropertyName('key.b.keyword') => [
                                'order'         => 'desc',
                                'unmapped_type' => 'keyword',
                            ],
                        ],
                    ],
                ],
                static function (Builder $builder): array {
                    $builder->orderBy(Configuration::getPropertyName('key.a'), 'asc');
                    $builder->orderBy(Configuration::getPropertyName('key.b'), 'desc');

                    return [
                        'a'   => new Text('a', true),
                        'key' => [
                            'a' => new Uuid('a', false),
                            'b' => new Text('b', false),
                        ],
                    ];
                },
            ],
            'offset'                => [
                [
                    'query' => [
                        'bool' => [
                            'must' => $must,
                        ],
                    ],
                    'from'  => 45,
                    'size'  => 123,
                ],
                static function (Builder $builder): void {
                    $builder->take(123);
                    $builder->offset(45);
                },
            ],
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public function dataProviderEscapeQueryString(): array {
        $a = new class('', true) extends Value {
            public function getType(): string {
                return 'text';
            }
        };
        $b = new class('', true) extends Value {
            public function getType(): string {
                return 'text';
            }
        };
        $c = new class('', false) extends Value {
            public function getType(): string {
                return 'text';
            }
        };

        return [
            ''                         => [
                [
                    'bool' => [
                        'must' => [
                            [
                                'match_all' => new stdClass(),
                            ],
                        ],
                    ],
                ],
                [
                    'a' => $a,
                    'b' => $b,
                ],
                '',
            ],
            '*'                        => [
                [
                    'bool' => [
                        'must' => [
                            [
                                'match_all' => new stdClass(),
                            ],
                        ],
                    ],
                ],
                [
                    'a' => $a,
                    'b' => $b,
                ],
                '*',
            ],
            '""'                       => [
                [
                    'bool' => [
                        'must' => [
                            [
                                'match_none' => new stdClass(),
                            ],
                        ],
                    ],
                ],
                [
                    'a' => $a,
                    'b' => $b,
                ],
                '""',
            ],
            '  ""  '                   => [
                [
                    'bool' => [
                        'must' => [
                            [
                                'match_none' => new stdClass(),
                            ],
                        ],
                    ],
                ],
                [
                    'a' => $a,
                    'b' => $b,
                ],
                '  ""  ',
            ],
            'no searchable properties' => [
                [
                    'bool' => [
                        'must' => [
                            [
                                'match_none' => new stdClass(),
                            ],
                        ],
                    ],
                ],
                [
                    'c' => $c,
                ],
                'search',
            ],
            'string (single)'          => [
                [
                    'bool' => [
                        'must' => [
                            [
                                'wildcard' => [
                                    Configuration::getPropertyName('a') => [
                                        'value'            => '*se\\*\\?ch*',
                                        'case_insensitive' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'a' => $a,
                ],
                'se*?ch',
            ],
            'string (multi)'           => [
                [
                    'bool' => [
                        'must' => [
                            'bool' => [
                                'should' => [
                                    [
                                        'wildcard' => [
                                            Configuration::getPropertyName('a') => [
                                                'value'            => '*se\\arch*',
                                                'case_insensitive' => true,
                                            ],
                                        ],
                                    ],
                                    [
                                        'wildcard' => [
                                            Configuration::getPropertyName('b') => [
                                                'value'            => '*se\\arch*',
                                                'case_insensitive' => true,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'a' => $a,
                    'b' => $b,
                ],
                'se\\arch',
            ],
            '"the phrase" (single)'    => [
                [
                    'bool' => [
                        'must' => [
                            [
                                'wildcard' => [
                                    Configuration::getPropertyName('a') => [
                                        'value'            => '*the phrase*',
                                        'case_insensitive' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'a' => $a,
                ],
                '"the phrase"',
            ],
            '"the phrase" (multi)'     => [
                [
                    'bool' => [
                        'must' => [
                            'bool' => [
                                'should' => [
                                    [
                                        'wildcard' => [
                                            Configuration::getPropertyName('a') => [
                                                'value'            => '*the phrase*',
                                                'case_insensitive' => true,
                                            ],
                                        ],
                                    ],
                                    [
                                        'wildcard' => [
                                            Configuration::getPropertyName('b') => [
                                                'value'            => '*the phrase*',
                                                'case_insensitive' => true,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'a' => $a,
                    'b' => $b,
                ],
                '"the phrase"',
            ],
            'words (single)'           => [
                [
                    'bool' => [
                        'must' => [
                            [
                                'wildcard' => [
                                    Configuration::getPropertyName('a') => [
                                        'value'            => '*one*two*',
                                        'case_insensitive' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'a' => $a,
                ],
                'one two',
            ],
            'words (multi)'            => [
                [
                    'bool' => [
                        'must' => [
                            'bool' => [
                                'should' => [
                                    [
                                        'wildcard' => [
                                            Configuration::getPropertyName('a') => [
                                                'value'            => '*one*two*',
                                                'case_insensitive' => true,
                                            ],
                                        ],
                                    ],
                                    [
                                        'wildcard' => [
                                            Configuration::getPropertyName('b') => [
                                                'value'            => '*one*two*',
                                                'case_insensitive' => true,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'a' => $a,
                    'b' => $b,
                ],
                'one two',
            ],
        ];
    }
    //</editor-fold>
}
