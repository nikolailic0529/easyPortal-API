<?php declare(strict_types = 1);

namespace App\Rules\Organization;

use App\Models\Organization;
use Closure;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Rules\Organization\Id
 */
class IdTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::message
     */
    public function testMessage(): void {
        $this->app->setLocale('de');
        $translationsFactory = static function (TestCase $test, string $locale): array {
            return [
                $locale => [
                    'validation.organization_id' => 'Translated',
                ],
            ];
        };
        $this->setTranslations($translationsFactory);
        $this->assertEquals($this->app->make(Id::class)->message(), 'Translated');
    }

    /**
     * @covers ::passes
     *
     * @dataProvider dataProviderPasses
     */
    public function testPasses(bool $expected, Closure $currencyFactory): void {
        $currencyId = $currencyFactory();
        $this->assertEquals($expected, $this->app->make(Id::class)->passes('test', $currencyId));
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
                    $currency = Organization::factory()->create([
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
                    $currency = Organization::factory()->create([
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
