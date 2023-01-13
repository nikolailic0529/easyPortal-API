<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\QuoteRequestDuration;
use Closure;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Rules\QuoteRequestDurationId
 */
class QuoteRequestDurationIdTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
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
     * @dataProvider dataProviderPasses
     *
     * @param Closure(static): ?string $valueFactory
     */
    public function testPasses(bool $expected, Closure $valueFactory): void {
        $rule   = $this->app->make(QuoteRequestDurationId::class);
        $value  = $valueFactory($this);
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
            'exists'       => [
                true,
                static function (): string {
                    return QuoteRequestDuration::factory()
                        ->create([
                            'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        ])
                        ->getKey();
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
                    return QuoteRequestDuration::factory()
                        ->create([
                            'deleted_at' => Date::now(),
                        ])
                        ->getKey();
                },
            ],
            'empty string' => [
                false,
                static function (): string {
                    return '';
                },
            ],
        ];
    }
    // </editor-fold>
}
