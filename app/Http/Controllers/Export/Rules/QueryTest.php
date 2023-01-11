<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Rules;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Translation\PotentiallyTranslatedString;
use Tests\TestCase;

use function array_merge;

/**
 * @internal
 * @covers \App\Http\Controllers\Export\Rules\Query
 */
class QueryTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderPasses
     *
     * @param array<mixed> $data
     */
    public function testPasses(bool $expected, mixed $value, array $data = []): void {
        $rule   = $this->app->make(Query::class);
        $actual = null;

        ((clone $rule)->setData($data))(
            'test',
            $value,
            function (string $message) use (&$actual): PotentiallyTranslatedString {
                $actual = new PotentiallyTranslatedString($message, $this->app->make(Translator::class));

                return $actual;
            },
        );
        $passes = !$this->app->make(Factory::class)
            ->make(array_merge($data, ['value' => $value]), ['value' => $rule])
            ->fails();

        self::assertEquals($expected, !$actual);
        self::assertEquals($expected, $passes);
    }

    public function testMessage(): void {
        $this->setTranslations(static function (TestCase $case, string $locale): array {
            return [
                $locale => [
                    'validation.http.controllers.export.query_invalid' => 'query_invalid',
                ],
            ];
        });

        $rule     = $this->app->make(Query::class);
        $actual   = null;
        $expected = 'query_invalid';

        $rule('attribute', null, function (string $message) use (&$actual): PotentiallyTranslatedString {
            $actual = new PotentiallyTranslatedString($message, $this->app->make(Translator::class));

            return $actual;
        });

        self::assertNotNull($actual);
        self::assertEquals($expected, (string) $actual);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderPasses(): array {
        return [
            'empty string'                           => [
                false,
                '',
                [
                    // empty
                ],
            ],
            'valid string query without variables'   => [
                true,
                <<<'GRAPHQL'
                query {
                    assets (limit: 1) {
                        id
                    }
                }
                GRAPHQL,
                [
                    // empty
                ],
            ],
            'invalid string query without variables' => [
                false,
                <<<'GRAPHQL'
                query {
                    assets {
                        unknown
                    }
                }
                GRAPHQL,
                [
                    // empty
                ],
            ],
            'valid string query with variables'      => [
                true,
                <<<'GRAPHQL'
                query test($limit: Int!) {
                    assets (limit: $limit) {
                        id
                    }
                }
                GRAPHQL,
                [
                    'variables' => [
                        'limit' => 1,
                    ],
                ],
            ],
            'valid query without variables'          => [
                true,
                [
                    'query' => <<<'GRAPHQL'
                        query {
                            assets (limit: 1) {
                                id
                            }
                        }
                    GRAPHQL
                    ,
                ],
                [
                    // empty
                ],
            ],
            'invalid query without variables'        => [
                false,
                [
                    'query' => <<<'GRAPHQL'
                        query {
                            assets {
                                unknown
                            }
                        }
                    GRAPHQL
                    ,
                ],
                [
                    // empty
                ],
            ],
            'valid query with variables'             => [
                true,
                [
                    'query'     => <<<'GRAPHQL'
                        query test($limit: Int!) {
                            assets (limit: $limit) {
                                id
                            }
                        }
                    GRAPHQL
                    ,
                    'variables' => [
                        'limit' => 1,
                    ],
                ],
                [
                    // empty
                ],
            ],
            'valid query with fragment'              => [
                true,
                [
                    'query'     => /** @lang GraphQL */
                        <<<'GRAPHQL'
                        query test($limit: Int!) {
                            assets (limit: $limit) {
                                ...AssetProperties
                            }
                        }

                        fragment AssetProperties on Asset {
                            id
                        }
                        GRAPHQL
                    ,
                    'variables' => [
                        'limit' => 1,
                    ],
                ],
                [
                    // empty
                ],
            ],
            'mutation is not allowed'                => [
                false,
                <<<'GRAPHQL'
                mutation {
                    setApplicationLocale(input: { locale: "locale" }) {
                        result
                    }
                }
                GRAPHQL,
                [
                    // empty
                ],
            ],
            'only one query allowed'                 => [
                false,
                <<<'GRAPHQL'
                query a {
                    assets {
                        id
                    }
                }
                query b {
                    assets {
                        id
                    }
                }
                GRAPHQL,
                [
                    // empty
                ],
            ],
            'invalid variable value'                 => [
                false,
                <<<'GRAPHQL'
                query test($limit: Int!) {
                    assets (limit: $limit) {
                        id
                    }
                }
                GRAPHQL,
                [
                    'variables' => [
                        'limit' => 'not a number',
                    ],
                ],
            ],
            'invalid graphql'                        => [
                false,
                [
                    'query' => <<<'GRAPHQL'
                        not a graphql
                    GRAPHQL
                    ,
                ],
                [
                    // empty
                ],
            ],
        ];
    }
    // </editor-fold>
}
