<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Rules;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Translation\PotentiallyTranslatedString;
use Mockery;
use Tests\TestCase;

use function implode;
use function json_encode;
use function value;

use const JSON_THROW_ON_ERROR;

/**
 * @internal
 * @covers \App\Http\Controllers\Export\Rules\Selector
 *
 * @phpstan-type Expected array{passed: bool, messages: array<mixed>}
 */
class SelectorTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param Expected $expected
     */
    public function testInvoke(array $expected, mixed $value): void {
        // Mock
        $this->app->instance('translator', value(static function (): Translator {
            $translator = Mockery::mock(Translator::class);
            $translator
                ->shouldReceive('get')
                ->andReturnUsing(static function (string $key): string {
                    return $key;
                });

            return $translator;
        }));

        // Test
        $rule     = $this->app->make(Selector::class);
        $messages = [];

        $rule(
            'value',
            $value,
            static function (string $message) use (&$messages): PotentiallyTranslatedString {
                $messages[] = $message;
                $string     = Mockery::mock(PotentiallyTranslatedString::class);

                return $string;
            },
        );

        self::assertEquals($expected, [
            'passed'   => $messages === [],
            'messages' => $messages,
        ]);
    }

    /**
     * @dataProvider dataProviderQuery
     *
     * @param Expected $expected
     */
    public function testQuery(array $expected, string $root, ?string $query, string $selector): void {
        // Mock
        $this->app->instance('translator', value(static function (): Translator {
            $translator = Mockery::mock(Translator::class);
            $translator
                ->shouldReceive('get')
                ->andReturnUsing(static function (string $key, array $replace = []): string {
                    return $key.($replace ? ': '.json_encode($replace, JSON_THROW_ON_ERROR) : '');
                });

            return $translator;
        }));

        // Test
        $validator = $this->app->make(Factory::class)->make(
            [
                'root'     => $root,
                'query'    => $query,
                'selector' => $selector,
            ],
            [
                'query'    => ['nullable', $this->app->make(Query::class)],
                'selector' => ['required', $this->app->make(Selector::class)],
            ],
        );
        $passed    = $validator->fails() === false;
        $messages  = $validator->getMessageBag()->toArray();

        self::assertEquals($expected, [
            'passed'   => $passed,
            'messages' => $messages,
        ]);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array{Expected, mixed}>
     */
    public function dataProviderInvoke(): array {
        return [
            'empty string'                              => [
                [
                    'passed'   => false,
                    'messages' => [
                        'validation.http.controllers.export.selector_required',
                    ],
                ],
                '',
            ],
            'property'                                  => [
                [
                    'passed'   => true,
                    'messages' => [],
                ],
                'property',
            ],
            'nested property'                           => [
                [
                    'passed'   => true,
                    'messages' => [],
                ],
                'property.a.b',
            ],
            'known function'                            => [
                [
                    'passed'   => true,
                    'messages' => [],
                ],
                'concat(a, b)',
            ],
            'unknown function'                          => [
                [
                    'passed'   => false,
                    'messages' => [
                        'http.controllers.export.selector_function_unknown',
                    ],
                ],
                'unknown(a, b)',
            ],
            'known function with invalid number of arg' => [
                [
                    'passed'   => false,
                    'messages' => [
                        'http.controllers.export.selector_function_too_few_arguments',
                    ],
                ],
                'concat()',
            ],
            'nested known function'                     => [
                [
                    'passed'   => true,
                    'messages' => [],
                ],
                'concat(a, b, or(c, d))',
            ],
            'invalid function call'                     => [
                [
                    'passed'   => false,
                    'messages' => [
                        'http.controllers.export.selector_syntax_error',
                    ],
                ],
                'concat(a, or(a, b)',
            ],
        ];
    }

    /**
     * @return array<string, array{Expected, string, ?string, string}>
     */
    public function dataProviderQuery(): array {
        return [
            'no query'                 => [
                [
                    'passed'   => false,
                    'messages' => [
                        'selector' => [
                            'validation.http.controllers.export.query_required',
                        ],
                    ],
                ],
                'data.assets',
                null,
                'property',
            ],
            'all properties are known' => [
                [
                    'passed'   => true,
                    'messages' => [
                        // empty
                    ],
                ],
                'data.assets',
                /** @lang GraphQL */
                <<<'GRAPHQL'
                query {
                    assets {
                        id
                        nickname
                        product {
                            name
                        }
                        location {
                            country {
                                name
                            }
                            city {
                                name
                            }
                        }
                        coverages {
                            name
                        }
                        alias: product {
                            name
                        }
                        ...AssetTags
                        status {
                            ... on Status {
                              name
                            }
                        }
                    }
                }

                fragment AssetTags on Asset {
                    tags {
                        name
                    }
                }
                GRAPHQL,
                'concat(or(nickname, alias.name), location.city.name, coverages.*.name, tags.*.name, status.name)',
            ],
            'unknown properties'       => [
                [
                    'passed'   => false,
                    'messages' => [
                        'selector' => [
                            'validation.http.controllers.export.selector_unknown: '.json_encode(
                                [
                                    'unknown' => implode(', ', [
                                        'nickname',
                                        'alias.name',
                                        'location.city.name',
                                        'coverages.*.name',
                                        'tags.*.name',
                                        'status.name',
                                    ]),
                                ],
                                JSON_THROW_ON_ERROR,
                            ),
                        ],
                    ],
                ],
                'data.assets',
                /** @lang GraphQL */
                <<<'GRAPHQL'
                query {
                    assets {
                        id
                    }
                }
                GRAPHQL,
                'concat(or(nickname, alias.name), location.city.name, coverages.*.name, tags.*.name, status.name)',
            ],
        ];
    }
    // </editor-fold>
}
