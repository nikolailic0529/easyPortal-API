<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Distributor;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Resolver\Resolvers\DistributorResolver
 */
class DistributorResolverTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::get
     */
    public function testGet(): void {
        // Prepare
        $factory = static function (?Distributor $distributor): Distributor {
            return $distributor ?? Distributor::factory()->make();
        };

        $a = Distributor::factory()->create();
        $b = Distributor::factory()->create();

        self::assertTrue($b->delete());
        self::assertTrue($b->trashed());

        // Run
        $provider = $this->app->make(DistributorResolver::class);
        $actual   = $provider->get($a->getKey(), $factory);

        // Basic
        self::assertNotEmpty($actual);
        self::assertEquals($a->getKey(), $actual->getKey());

        // Second call should return same instance
        $queries = $this->getQueryLog()->flush();

        self::assertSame($actual, $provider->get($a->getKey(), $factory));
        self::assertSame($actual, $provider->get(" {$a->getKey()} ", $factory));

        self::assertCount(0, $queries);

        // All value should be loaded, so get() should not perform any queries
        $queries = $this->getQueryLog()->flush();

        self::assertNotSame($actual, $provider->get($b->getKey(), $factory));
        self::assertCount(0, $queries);

        // If value not found the new object should be created
        $uuid    = $this->faker->uuid();
        $spy     = Mockery::spy(static function () use ($uuid): Distributor {
            return Distributor::factory()->make([
                'id' => $uuid,
            ]);
        });
        $queries = $this->getQueryLog()->flush();
        $created = $provider->get($uuid, Closure::fromCallable($spy));

        $spy->shouldHaveBeenCalled();

        self::assertNotEmpty($created);
        self::assertEquals($uuid, $created->getKey());
        self::assertCount(1, $queries);

        // The created object should be in cache
        $queries = $this->getQueryLog()->flush();

        self::assertSame($created, $provider->get($uuid, $factory));
        self::assertCount(0, $queries);

        // Created object should be found
        $c       = Distributor::factory()->create();
        $queries = $this->getQueryLog()->flush();

        self::assertEquals($c->getKey(), $provider->get($c->getKey())?->getKey());
        self::assertCount(1, $queries);
    }
}
