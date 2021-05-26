<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\AssetCoverage;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Resolvers\AssetCoverageResolver
 */
class AssetCoverageResolverTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::get
     */
    public function testGet(): void {
        // Prepare
        $factory = static function (): AssetCoverage {
            return AssetCoverage::factory()->make();
        };

        AssetCoverage::factory()->create(['key' => 'a']);
        AssetCoverage::factory()->create(['key' => 'b']);
        AssetCoverage::factory()->create(['key' => 'c']);

        // Run
        $provider = $this->app->make(AssetCoverageResolver::class);
        $actual   = $provider->get('a', $factory);

        $this->flushQueryLog();

        // Basic
        $this->assertNotNull($actual);
        $this->assertEquals('a', $actual->key);

        // Second call should return same instance
        $this->assertSame($actual, $provider->get('a', $factory));
        $this->assertSame($actual, $provider->get(' a ', $factory));
        $this->assertSame($actual, $provider->get('A', $factory));
        $this->assertCount(0, $this->getQueryLog());

        // All value should be loaded, so get() should not perform any queries
        $this->assertNotNull($provider->get('b', $factory));
        $this->assertCount(0, $this->getQueryLog());

        $this->assertNotNull($provider->get('c', $factory));
        $this->assertCount(0, $this->getQueryLog());
    }
}