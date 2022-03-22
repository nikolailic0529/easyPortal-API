<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\QuoteRequestDuration;
use Closure;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Rules\QuoteRequestDurationId
 */
class QuoteRequestDurationIdTest extends TestCase {
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
                    'validation.quote_request_duration_id' => 'Translated',
                ],
            ];
        };
        $this->setTranslations($translationsFactory);
        self::assertEquals($this->app->make(QuoteRequestDurationId::class)->message(), 'Translated');
    }

    /**
     * @covers ::passes
     *
     * @dataProvider dataProviderPasses
     */
    public function testPasses(bool $expected, Closure $durationFactory): void {
        $durationId = $durationFactory();
        self::assertEquals($expected, $this->app->make(QuoteRequestDurationId::class)->passes('test', $durationId));
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
                    $duration = QuoteRequestDuration::factory()->create([
                        'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                    ]);
                    return $duration->getKey();
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
                    $duration = QuoteRequestDuration::factory()->create([
                        'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                    ]);
                    $duration->delete();
                    return $duration->getKey();
                },
            ],
        ];
    }
    // </editor-fold>
}
