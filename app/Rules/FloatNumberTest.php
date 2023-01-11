<?php declare(strict_types = 1);

namespace App\Rules;

use Illuminate\Contracts\Validation\Factory;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Rules\FloatNumber
 */
class FloatNumberTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderPasses
     */
    public function testPasses(bool $expected, mixed $value): void {
        $rule   = $this->app->make(FloatNumber::class);
        $actual = $rule->passes('test', $value);
        $passes = !$this->app->make(Factory::class)
            ->make(['value' => $value], ['value' => $rule])
            ->fails();

        self::assertEquals($expected, $actual);
        self::assertEquals($expected, $passes);
    }

    public function testMessage(): void {
        $this->setTranslations(static function (TestCase $case, string $locale): array {
            return [
                $locale => [
                    'validation.float' => 'message validation.float',
                ],
            ];
        });

        self::assertEquals('message validation.float', (new FloatNumber())->message());
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderPasses(): array {
        return [
            'bool'      => [false, false],
            'string'    => [false, 'sdf'],
            'string123' => [false, 'sdf123'],
            '"123"'     => [true, '123'],
            '123'       => [true, 123],
            '123.124'   => [true, 123.123],
            '"123,124"' => [false, '123,123'],
            '0'         => [true, 0],
            '``'        => [false, ''],
        ];
    }
    // </editor-fold>
}
