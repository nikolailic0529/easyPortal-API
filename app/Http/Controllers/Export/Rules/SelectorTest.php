<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Rules;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Validation\Factory;
use Mockery;
use Tests\TestCase;

use function value;

/**
 * @internal
 * @coversDefaultClass \App\Http\Controllers\Export\Rules\Selector
 */
class SelectorTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderInvoke
     *
     * @param array{passed: bool, messages: array<string>} $expected
     */
    public function testPasses(array $expected, mixed $value): void {
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
        $rule      = $this->app->make(Selector::class);
        $validator = $this->app->make(Factory::class)->make(['value' => $value], ['value' => $rule]);
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
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        return [
            'empty string'                              => [
                [
                    'passed'   => false,
                    'messages' => [
                        'value' => [
                            'validation.http.controllers.export.selector_required',
                        ],
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
                        'value' => [
                            'http.controllers.export.selector_function_unknown',
                        ],
                    ],
                ],
                'unknown(a, b)',
            ],
            'known function with invalid number of arg' => [
                [
                    'passed'   => false,
                    'messages' => [
                        'value' => [
                            'http.controllers.export.selector_function_too_few_arguments',
                        ],
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
                        'value' => [
                            'http.controllers.export.selector_syntax_error',
                        ],
                    ],
                ],
                'concat(a, or(a, b)',
            ],
        ];
    }
    // </editor-fold>
}
