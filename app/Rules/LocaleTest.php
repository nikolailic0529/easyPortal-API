<?php declare(strict_types = 1);

namespace App\Rules;

use Illuminate\Contracts\Validation\Factory;
use Illuminate\Translation\Translator;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Rules\Locale
 */
class LocaleTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    public function testMessage(): void {
        $translator = $this->app->make(Translator::class);
        $translator->addLines(['validation.locale' => 'No translation'], 'en');
        $translator->addLines(['validation.locale' => 'Translated (locale)'], 'de');
        $this->app->setLocale('de');
        self::assertEquals(
            $this->app->make(Locale::class)->message(),
            $translator->get('validation.locale', [], 'de'),
        );
    }

    /**
     * @dataProvider dataProviderPasses
     */
    public function testPasses(bool $expected, string $value): void {
        $rule   = $this->app->make(Locale::class);
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
            'valid'                 => [true, 'en'],
            'valid with underscore' => [true, 'en_BB'],
            'invalid'               => [false, 'wrong'],
            'empty string'          => [false, ''],
        ];
    }
    // </editor-fold>
}
