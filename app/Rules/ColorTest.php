<?php declare(strict_types = 1);

namespace App\Rules;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Rules\Color
 */
class ColorTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::message
     */
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
     * @covers ::passes
     *
     * @dataProvider dataProviderPasses
     */
    public function testPasses(bool $expected, string $value): void {
        self::assertEquals($expected, $this->app->make(Color::class)->passes('test', $value));
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
        ];
    }
    // </editor-fold>
}
