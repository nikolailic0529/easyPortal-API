<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Asset;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;
use Tests\WithoutOrganizationScope;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Resolver\Resolvers\AssetResolver
 */
class AssetResolverTest extends TestCase {
    use WithoutOrganizationScope;
    use WithQueryLog;

    /**
     * @covers ::get
     */
    public function testGet(): void {
        // Prepare
        $factory = static function (): Asset {
            return Asset::factory()->make();
        };

        $a = Asset::factory()->create();
        $b = Asset::factory()->create();

        // Run
        $provider = $this->app->make(AssetResolver::class);
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
        $spy     = Mockery::spy(static function () use ($uuid): Asset {
            return Asset::factory()->make([
                'id'          => $uuid,
                'oem_id'      => $uuid,
                'type_id'     => $uuid,
                'product_id'  => $uuid,
                'customer_id' => $uuid,
                'location_id' => $uuid,
                'reseller_id' => $uuid,
                'status_id'   => null,
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
