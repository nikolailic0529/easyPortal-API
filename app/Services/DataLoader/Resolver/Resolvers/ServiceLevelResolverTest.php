<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Oem;
use App\Models\ServiceGroup;
use App\Models\ServiceLevel;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Resolver\Resolvers\ServiceLevelResolver
 */
class ServiceLevelResolverTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::get
     */
    public function testGet(): void {
        // Prepare
        $oemA    = Oem::factory()->create();
        $oemB    = Oem::factory()->create();
        $groupA  = ServiceGroup::factory()->create();
        $groupB  = ServiceGroup::factory()->create();
        $factory = static function (): ServiceLevel {
            return ServiceLevel::factory()->make();
        };

        $a = ServiceLevel::factory()->create([
            'oem_id'           => $oemA,
            'service_group_id' => $groupA,
            'sku'              => 'a',
        ]);
        $b = ServiceLevel::factory()->create([
            'oem_id'           => $oemA,
            'service_group_id' => $groupA,
            'sku'              => 'b',
        ]);

        // Run
        $provider = $this->app->make(ServiceLevelResolver::class);
        $actual   = $provider->get($oemA, $groupA, ' a ', $factory);

        $this->flushQueryLog();

        // Basic
        $this->assertNotNull($actual);
        $this->assertFalse($actual->wasRecentlyCreated);
        $this->assertEquals('a', $actual->sku);
        $this->assertEquals($a->name, $actual->name);
        $this->assertEquals($oemA, $actual->oem);

        $this->flushQueryLog();

        // Second call should return same instance
        $this->assertSame($actual, $provider->get($oemA, $groupA, 'a', $factory));
        $this->assertSame($actual, $provider->get($oemA, $groupA, ' a ', $factory));
        $this->assertSame($actual, $provider->get($oemA, $groupA, 'A', $factory));
        $this->assertCount(0, $this->getQueryLog());

        $this->assertNotSame($actual, $provider->get($oemB, $groupA, 'a', static function (): ServiceLevel {
            return ServiceLevel::factory()->make();
        }));

        $this->flushQueryLog();

        // All value should be loaded, so get() should not perform any queries
        $this->assertNotNull($provider->get($oemA, $groupA, $b->sku, $factory));
        $this->assertCount(0, $this->getQueryLog());

        // If value not found the new object should be created
        $spy     = Mockery::spy(static function () use ($oemB, $groupB): ServiceLevel {
            return ServiceLevel::factory()->create([
                'oem_id'           => $oemB,
                'service_group_id' => $groupB,
                'sku'              => 'unKnown',
            ]);
        });
        $created = $provider->get($oemB, $groupB, ' unKnown ', Closure::fromCallable($spy));

        $spy->shouldHaveBeenCalled();

        $this->assertNotNull($created);
        $this->assertEquals('unKnown', $created->sku);
        $this->assertEquals($oemB->getKey(), $created->oem_id);
        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // The created object should be in cache
        $this->assertSame($created, $provider->get($oemB, $groupB, ' unknown ', $factory));
        $this->assertCount(0, $this->getQueryLog());

        // Created object should NOT be found
        $c = ServiceLevel::factory()->create([
            'oem_id'           => $oemB,
            'service_group_id' => $groupB,
        ]);

        $this->flushQueryLog();
        $this->assertNull($provider->get($oemB, $groupB, $c->sku));
        $this->assertCount(0, $this->getQueryLog());
        $this->flushQueryLog();
    }
}
