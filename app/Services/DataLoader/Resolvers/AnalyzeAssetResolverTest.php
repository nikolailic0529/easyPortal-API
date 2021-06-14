<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Logs\AnalyzeAsset;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;
use Tests\WithoutOrganizationScope;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Resolvers\AnalyzeAssetResolver
 */
class AnalyzeAssetResolverTest extends TestCase {
    use WithoutOrganizationScope;
    use WithQueryLog;

    /**
     * @covers ::get
     */
    public function testGet(): void {
        $this->markTestSkipped('getQueryLog cannot work with the non-default connection.');

        // Prepare
        $factory = static function (): AnalyzeAsset {
            return AnalyzeAsset::factory()->make();
        };

        $a = AnalyzeAsset::factory()->create();
        $b = AnalyzeAsset::factory()->create();

        // Run
        $provider = $this->app->make(AnalyzeAssetResolver::class);
        $actual   = $provider->get($a->getKey(), $factory);

        $this->flushQueryLog();

        // Basic
        $this->assertNotNull($actual);
        $this->assertEquals($a->getKey(), $actual->getKey());

        // Second call should return same instance
        $this->assertSame($actual, $provider->get($a->getKey(), $factory));
        $this->assertSame($actual, $provider->get(" {$a->getKey()} ", $factory));

        $this->assertCount(0, $this->getQueryLog());

        $this->assertNotSame($actual, $provider->get($b->getKey(), $factory));
        $this->assertCount(1, $this->getQueryLog());
        $this->flushQueryLog();

        // If value not found the new object should be created
        $uuid    = $this->faker->uuid;
        $spy     = Mockery::spy(static function () use ($uuid): AnalyzeAsset {
            return AnalyzeAsset::factory()->make([
                'id'          => $uuid,
            ]);
        });
        $created = $provider->get($uuid, Closure::fromCallable($spy));

        $spy->shouldHaveBeenCalled();

        $this->assertNotNull($created);
        $this->assertEquals($uuid, $created->getKey());
        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // The created object should be in cache
        $this->assertSame($created, $provider->get($uuid, $factory));
        $this->assertCount(0, $this->getQueryLog());
    }
}
