<?php declare(strict_types = 1);

namespace App\Rules;

use Illuminate\Translation\Translator;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Rules\Locale
 */
class LocaleTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::message
     */
    public function testMessage(): void {
        $translator = $this->app->make(Translator::class);
        $translator->addLines(['validation.locale' => 'No translation'], 'en');
        $translator->addLines(['validation.locale' => 'Translated (locale)'], 'de');
        $this->app->setLocale('de');
        $this->assertEquals(
            $this->app->make(Locale::class)->message(),
            $translator->get('validation.locale', [], 'de'),
        );
    }

    /**
     * @covers ::passes
     *
     * @dataProvider dataProviderPasses
     */
    public function testPasses(bool $expected, string $value): void {
        $this->assertEquals($expected, $this->app->make(Locale::class)->passes('test', $value));
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderPasses(): array {
        return [
            'valid'                 => [true, 'en'],
            'valid with underscore' => [true, 'en_BB'],
            'Invalid'               => [false, 'wrong'],
        ];
    }
    // </editor-fold>
}
