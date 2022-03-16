<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Distributor;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
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
        $factory = static function (): Distributor {
            return Distributor::factory()->make();
        };

        $a = Distributor::factory()->create();
        $b = Distributor::factory()->create();

        // Run
        $provider = $this->app->make(DistributorResolver::class);
        $actual   = $provider->get($a->getKey(), $factory);

        $this->flushQueryLog();

        // Basic
        self::assertNotNull($actual);
        self::assertEquals($a->getKey(), $actual->getKey());

        // Second call should return same instance
        self::assertSame($actual, $provider->get($a->getKey(), $factory));
        self::assertSame($actual, $provider->get(" {$a->getKey()} ", $factory));

        self::assertCount(0, $this->getQueryLog());

        // All value should be loaded, so get() should not perform any queries
        self::assertNotSame($actual, $provider->get($b->getKey(), $factory));
        self::assertCount(0, $this->getQueryLog());
        $this->flushQueryLog();

        // If value not found the new object should be created
        $uuid    = $this->faker->uuid;
        $spy     = Mockery::spy(static function () use ($uuid): Distributor {
            return Distributor::factory()->make([
                'id' => $uuid,
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

        // Created object should be found
        $c = Distributor::factory()->create();

        $this->flushQueryLog();
        self::assertEquals($c->getKey(), $provider->get($c->getKey())?->getKey());
        self::assertCount(1, $this->getQueryLog());
        $this->flushQueryLog();
    }
}
