<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Oem;
use App\Models\ServiceGroup;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Resolver\Resolvers\ServiceGroupResolver
 */
class ServiceGroupResolverTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::get
     */
    public function testGet(): void {
        // Prepare
        $oemA    = Oem::factory()->create();
        $oemB    = Oem::factory()->create();
        $factory = static function (): ServiceGroup {
            return ServiceGroup::factory()->make();
        };

        $a = ServiceGroup::factory()->create([
            'oem_id' => $oemA,
            'sku'    => 'a',
        ]);
        $b = ServiceGroup::factory()->create([
            'oem_id' => $oemA,
            'sku'    => 'b',
        ]);

        // Run
        $provider = $this->app->make(ServiceGroupResolver::class);
        $actual   = $provider->get($oemA, ' a ', $factory);

        $this->flushQueryLog();

        // Basic
        $this->assertNotNull($actual);
        $this->assertFalse($actual->wasRecentlyCreated);
        $this->assertEquals('a', $actual->sku);
        $this->assertEquals($a->name, $actual->name);
        $this->assertEquals($oemA, $actual->oem);

        $this->flushQueryLog();

        // Second call should return same instance
        $this->assertSame($actual, $provider->get($oemA, 'a', $factory));
        $this->assertSame($actual, $provider->get($oemA, ' a ', $factory));
        $this->assertSame($actual, $provider->get($oemA, 'A', $factory));
        $this->assertCount(0, $this->getQueryLog());

        $this->assertNotSame($actual, $provider->get($oemB, 'a', static function (): ServiceGroup {
            return ServiceGroup::factory()->make();
        }));

        $this->flushQueryLog();

        // All value should be loaded, so get() should not perform any queries
        $this->assertNotNull($provider->get($oemA, $b->sku, $factory));
        $this->assertCount(0, $this->getQueryLog());

        // If value not found the new object should be created
        $spy     = Mockery::spy(static function () use ($oemB): ServiceGroup {
            return ServiceGroup::factory()->create([
                'oem_id' => $oemB,
                'sku'    => 'unKnown',
            ]);
        });
        $created = $provider->get($oemB, ' unKnown ', Closure::fromCallable($spy));

        $spy->shouldHaveBeenCalled();

        $this->assertNotNull($created);
        $this->assertEquals('unKnown', $created->sku);
        $this->assertEquals($oemB->getKey(), $created->oem_id);
        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // The created object should be in cache
        $this->assertSame($created, $provider->get($oemB, ' unknown ', $factory));
        $this->assertCount(0, $this->getQueryLog());

        // Created object should NOT be found
        $c = ServiceGroup::factory()->create([
            'oem_id' => $oemB,
        ]);

        $this->flushQueryLog();
        $this->assertNull($provider->get($oemB, $c->sku));
        $this->assertCount(0, $this->getQueryLog());
        $this->flushQueryLog();
    }
}
