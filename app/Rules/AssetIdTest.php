<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Asset;
use App\Models\Organization;
use App\Models\Reseller;
use Closure;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Rules\AssetId
 */
class AssetIdTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::message
     */
    public function testMessage(): void {
        $this->app->setLocale('de');
        $this->setTranslations(static function (TestCase $test, string $locale): array {
            return [
                $locale => [
                    'validation.asset_id' => 'Translated',
                ],
            ];
        });
        self::assertEquals($this->app->make(AssetId::class)->message(), 'Translated');
    }

    /**
     * @covers ::passes
     *
     * @param array<string, mixed> $settings
     * @dataProvider dataProviderPasses
     */
    public function testPasses(bool $expected, Closure $assetFactory): void {
        $organization = $this->setOrganization(Organization::factory()->create());
        $assetId      = $assetFactory($this, $organization);
        self::assertEquals($expected, $this->app->make(AssetId::class)->passes('test', $assetId));
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
                static function (TestCase $test, Organization $organization): string {
                    $reseller = Reseller::factory()->create([
                        'id' => $organization->getKey(),
                    ]);
                    $asset    = Asset::factory()->create([
                        'id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        'reseller_id' => $reseller->getKey(),
                    ]);
                    return $asset->getKey();
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
                static function (TestCase $test, Organization $organization): string {
                    $reseller = Reseller::factory()->create([
                        'id' => $organization->getKey(),
                    ]);
                    $asset    = Asset::factory()->create([
                        'id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        'reseller_id' => $reseller->getKey(),
                    ]);
                    $asset->delete();
                    return $asset->getKey();
                },
            ],
        ];
    }
    // </editor-fold>
}
