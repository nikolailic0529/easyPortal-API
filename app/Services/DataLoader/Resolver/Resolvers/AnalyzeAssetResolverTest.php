<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Logs\AnalyzeAsset;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;
use Tests\WithoutGlobalScopes;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Resolver\Resolvers\AnalyzeAssetResolver
 */
class AnalyzeAssetResolverTest extends TestCase {
    use WithoutGlobalScopes;
    use WithQueryLog;

    /**
     * @covers ::get
     */
    public function testGet(): void {
        self::markTestSkipped('getQueryLog cannot work with the non-default connection.');

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
        self::assertNotNull($actual);
        self::assertEquals($a->getKey(), $actual->getKey());

        // Second call should return same instance
        self::assertSame($actual, $provider->get($a->getKey(), $factory));
        self::assertSame($actual, $provider->get(" {$a->getKey()} ", $factory));

        self::assertCount(0, $this->getQueryLog());

        self::assertNotSame($actual, $provider->get($b->getKey(), $factory));
        self::assertCount(1, $this->getQueryLog());
        $this->flushQueryLog();

        // If value not found the new object should be created
        $uuid    = $this->faker->uuid();
        $spy     = Mockery::spy(static function () use ($uuid): AnalyzeAsset {
            return AnalyzeAsset::factory()->make([
                'id'          => $uuid,
            ]);
        });
        $created = $provider->get($uuid, Closure::fromCallable($spy));

        $spy->shouldHaveBeenCalled();

        self::assertNotNull($created);
        self::assertEquals($uuid, $created->getKey());
        self::assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // The created object should be in cache
        self::assertSame($created, $provider->get($uuid, $factory));
        self::assertCount(0, $this->getQueryLog());
    }
}
