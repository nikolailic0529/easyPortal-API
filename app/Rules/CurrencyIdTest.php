<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Currency;
use Closure;
use Illuminate\Translation\Translator;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Rules\CurrencyId
 */
class CurrencyIdTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::message
     */
    public function testMessage(): void {
        $translator = $this->app->make(Translator::class);
        $translator->addLines(['validation.currencyId' => 'No translation'], 'en');
        $translator->addLines(['validation.currencyId' => 'Translated (locale)'], 'de');
        $this->app->setLocale('de');
        $this->assertEquals(
            $this->app->make(CurrencyId::class)->message(),
            $translator->get('validation.currencyId', [], 'de'),
        );
    }

    /**
     * @covers ::passes
     *
     * @dataProvider dataProviderPasses
     */
    public function testPasses(bool $expected, Closure $currencyFactory): void {
        $currencyId = $currencyFactory();
        $this->assertEquals($expected, $this->app->make(CurrencyId::class)->passes('test', $currencyId));
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderPasses(): array {
        return [
            'exists'       => [
                true,
                static function (): string {
                    $currency = Currency::factory()->create([
                        'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                    ]);
                    return $currency->id;
                },
            ],
            'not-exists'   => [
                false,
                static function (): string {
                    return 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982';
                },
            ],
            'soft-deleted' => [
                false,
                static function (): string {
                    $currency = Currency::factory()->create([
                        'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                    ]);
                    $currency->delete();
                    return $currency->id;
                },
            ],
        ];
    }
    // </editor-fold>
}
