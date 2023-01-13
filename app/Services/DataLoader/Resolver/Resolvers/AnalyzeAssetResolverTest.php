<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Logs\AnalyzeAsset;
use App\Models\Logs\Model;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
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
        // Prepare
        $connection = Model::CONNECTION;
        $factory    = static function (?AnalyzeAsset $asset): AnalyzeAsset {
            return $asset ?? AnalyzeAsset::factory()->make();
        };

        $a = AnalyzeAsset::factory()->create();
        $b = AnalyzeAsset::factory()->create();

        // Run
        $provider = $this->app->make(AnalyzeAssetResolver::class);
        $actual   = $provider->get($a->getKey(), $factory);

        // Basic
        self::assertNotEmpty($actual);
        self::assertEquals($a->getKey(), $actual->getKey());

        // Second call should return same instance
        $queries = $this->getQueryLog($connection)->flush();

        self::assertSame($actual, $provider->get($a->getKey(), $factory));
        self::assertSame($actual, $provider->get(" {$a->getKey()} ", $factory));

        self::assertCount(0, $queries);

        self::assertNotSame($actual, $provider->get($b->getKey(), $factory));
        self::assertCount(1, $queries);

        // If value not found the new object should be created
        $uuid    = $this->faker->uuid();
        $spy     = Mockery::spy(static function () use ($uuid): AnalyzeAsset {
            return AnalyzeAsset::factory()->make([
                'id' => $uuid,
            ]);
        });
        $queries = $this->getQueryLog($connection)->flush();
        $created = $provider->get($uuid, Closure::fromCallable($spy));

        $spy->shouldHaveBeenCalled();

        self::assertEquals($uuid, $created->getKey());
        self::assertCount(1, $queries);

        // The created object should be in cache
        $queries = $this->getQueryLog($connection)->flush();

        self::assertSame($created, $provider->get($uuid, $factory));
        self::assertCount(0, $queries);
    }
}
