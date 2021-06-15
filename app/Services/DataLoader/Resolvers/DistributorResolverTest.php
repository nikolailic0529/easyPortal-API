<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Distributor;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Resolvers\DistributorResolver
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
        $this->assertNotNull($actual);
        $this->assertEquals($a->getKey(), $actual->getKey());

        // Second call should return same instance
        $this->assertSame($actual, $provider->get($a->getKey(), $factory));
        $this->assertSame($actual, $provider->get(" {$a->getKey()} ", $factory));

        $this->assertCount(0, $this->getQueryLog());

        // All value should be loaded, so get() should not perform any queries
        $this->assertNotSame($actual, $provider->get($b->getKey(), $factory));
        $this->assertCount(0, $this->getQueryLog());
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

        $this->assertNotNull($created);
        $this->assertEquals($uuid, $created->getKey());
        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // The created object should be in cache
        $this->assertSame($created, $provider->get($uuid, $factory));
        $this->assertCount(0, $this->getQueryLog());

        // Created object should be found
        $c = Distributor::factory()->create();

        $this->flushQueryLog();
        $this->assertEquals($c->getKey(), $provider->get($c->getKey())?->getKey());
        $this->assertCount(1, $this->getQueryLog());
        $this->flushQueryLog();
    }
}
