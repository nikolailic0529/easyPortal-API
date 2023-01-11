<?php declare(strict_types = 1);

namespace App\Utils\Validation;

use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\Rule;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Utils\Validation\Validator
 */
class ValidatorTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    public function testProvider(): void {
        $expected = Validator::class;
        $actual   = $this->app->make(Factory::class)
            ->make(
                [
                    'value' => '',
                ],
                [
                    'value' => 'required',
                ],
            );

        self::assertInstanceOf($expected, $actual);
    }

    /**
     * @dataProvider dataProviderValidation
     *
     * @param array<string,string|Rule>|string $rules
     */
    public function testValidation(bool $expected, array|string $rules, mixed $value): void {
        $validator = $this->app->make(Factory::class)->make(['value' => $value], ['value' => $rules]);
        $actual    = !$validator->fails();

        self::assertEquals($expected, $actual);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array{bool, array<string,string|Rule>|string, mixed}>
     */
    public function dataProviderValidation(): array {
        return [
            '`` => nullable'                       => [
                true,
                'nullable',
                '',
            ],
            '` ` => nullable'                      => [
                true,
                'nullable',
                '',
            ],
            '`null` => nullable'                   => [
                true,
                'nullable',
                null,
            ],
            '`` => required'                       => [
                false,
                'required',
                '',
            ],
            '` ` => required'                      => [
                false,
                'required',
                '',
            ],
            '`null` => required'                   => [
                false,
                'required',
                null,
            ],
            '`null` => nullable|required'          => [
                true,
                'nullable|required',
                null,
            ],
            '`` => non implicit rule (negative)'   => [
                false,
                'min:1',
                '',
            ],
            '` ` => non implicit rule (negative)'  => [
                false,
                'min:2',
                '',
            ],
            '`null` => non implicit rule'          => [
                false,
                'min:1',
                null,
            ],
            '`null` => nullable|non implicit rule' => [
                true,
                'nullable|min:1',
                null,
            ],
            '`` => non implicit rule (positive)'   => [
                true,
                'max:1',
                '',
            ],
            '` ` => non implicit rule (positive)'  => [
                true,
                'max:2',
                '',
            ],
        ];
    }
    // </editor-fold>
}
