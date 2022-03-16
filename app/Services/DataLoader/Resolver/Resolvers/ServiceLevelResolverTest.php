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
        self::assertNotNull($actual);
        self::assertFalse($actual->wasRecentlyCreated);
        self::assertEquals('a', $actual->sku);
        self::assertEquals($a->name, $actual->name);
        self::assertEquals($oemA, $actual->oem);

        $this->flushQueryLog();

        // Second call should return same instance
        self::assertSame($actual, $provider->get($oemA, $groupA, 'a', $factory));
        self::assertSame($actual, $provider->get($oemA, $groupA, ' a ', $factory));
        self::assertSame($actual, $provider->get($oemA, $groupA, 'A', $factory));
        self::assertCount(0, $this->getQueryLog());

        self::assertNotSame($actual, $provider->get($oemB, $groupA, 'a', static function (): ServiceLevel {
            return ServiceLevel::factory()->make();
        }));

        $this->flushQueryLog();

        // All value should be loaded, so get() should not perform any queries
        self::assertNotNull($provider->get($oemA, $groupA, $b->sku, $factory));
        self::assertCount(0, $this->getQueryLog());

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

        self::assertNotNull($created);
        self::assertEquals('unKnown', $created->sku);
        self::assertEquals($oemB->getKey(), $created->oem_id);
        self::assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // The created object should be in cache
        self::assertSame($created, $provider->get($oemB, $groupB, ' unknown ', $factory));
        self::assertCount(0, $this->getQueryLog());

        // Created object should NOT be found
        $c = ServiceLevel::factory()->create([
            'oem_id'           => $oemB,
            'service_group_id' => $groupB,
        ]);

        $this->flushQueryLog();
        self::assertNull($provider->get($oemB, $groupB, $c->sku));
        self::assertCount(0, $this->getQueryLog());
        $this->flushQueryLog();
    }
}
