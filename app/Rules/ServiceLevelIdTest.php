<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\ServiceLevel;
use Closure;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Rules\ServiceLevelId
 */
class ServiceLevelIdTest extends TestCase {
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
                    'validation.service_level_id' => 'Translated',
                ],
            ];
        };
        $this->setTranslations($translationsFactory);
        self::assertEquals($this->app->make(ServiceLevelId::class)->message(), 'Translated');
    }

    /**
     * @covers ::passes
     *
     * @dataProvider dataProviderPasses
     */
    public function testPasses(bool $expected, Closure $serviceLevelFactory): void {
        $serviceLevelId = $serviceLevelFactory();
        self::assertEquals($expected, $this->app->make(ServiceLevelId::class)->passes('test', $serviceLevelId));
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
                    $serviceLevel = ServiceLevel::factory()->create([
                        'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                    ]);
                    return $serviceLevel->getKey();
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
                    $serviceLevel = ServiceLevel::factory()->create([
                        'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                    ]);
                    $serviceLevel->delete();
                    return $serviceLevel->getKey();
                },
            ],
        ];
    }
    // </editor-fold>
}
