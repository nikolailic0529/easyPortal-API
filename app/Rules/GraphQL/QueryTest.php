<?php declare(strict_types = 1);

namespace App\Rules\GraphQL;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Rules\GraphQL\Query
 */
class QueryTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::passes
     *
     * @dataProvider dataProviderPasses
     *
     * @param array<mixed> $data
     */
    public function testPasses(bool $expected, mixed $value, array $data = []): void {
        $rule   = $this->app->make(Query::class);
        $actual = $rule->setData($data)->passes('test', $value);

        self::assertEquals($expected, $actual);
    }

    /**
     * @covers ::message
     */
    public function testMessage(): void {
        $this->setTranslations(static function (TestCase $case, string $locale): array {
            return [
                $locale => [
                    'validation.graphql_query' => 'message validation.graphql_query',
                ],
            ];
        });

        $rule     = $this->app->make(Query::class);
        $actual   = $rule->message();
        $expected = 'message validation.graphql_query';

        self::assertEquals($expected, $actual);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderPasses(): array {
        return [
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
        ];
    }
    // </editor-fold>
}
