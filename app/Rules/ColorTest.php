<?php declare(strict_types = 1);

namespace App\Rules;

use Illuminate\Contracts\Validation\Factory;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Rules\Color
 */
class ColorTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    public function testMessage(): void {
        $this->app->setLocale('de');
        $this->setTranslations([
            'de' => [
                'validation.color' => 'Translated (color)',
            ],
        ]);

        self::assertEquals(
            'Translated (color)',
            $this->app->make(Color::class)->message(),
        );
    }

    /**
     * @dataProvider dataProviderPasses
     */
    public function testPasses(bool $expected, string $value): void {
        $rule   = $this->app->make(Color::class);
        $actual = $rule->passes('test', $value);
        $passes = !$this->app->make(Factory::class)
            ->make(['value' => $value], ['value' => $rule])
            ->fails();

        self::assertEquals($expected, $actual);
        self::assertEquals($expected, $passes);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderPasses(): array {
        return [
            'valid'         => [true, '#FF00FF'],
            'invalid'       => [false, 'wrong'],
            'invalid color' => [false, '#FF0'],
            'empty string'  => [false, ''],
        ];
    }
    // </editor-fold>
}
