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
        self::assertNotNull($actual);
        self::assertFalse($actual->wasRecentlyCreated);
        self::assertEquals('a', $actual->sku);
        self::assertEquals($a->name, $actual->name);
        self::assertEquals($oemA, $actual->oem);

        $this->flushQueryLog();

        // Second call should return same instance
        self::assertSame($actual, $provider->get($oemA, 'a', $factory));
        self::assertSame($actual, $provider->get($oemA, ' a ', $factory));
        self::assertSame($actual, $provider->get($oemA, 'A', $factory));
        self::assertCount(0, $this->getQueryLog());

        self::assertNotSame($actual, $provider->get($oemB, 'a', static function (): ServiceGroup {
            return ServiceGroup::factory()->make();
        }));

        $this->flushQueryLog();

        // All value should be loaded, so get() should not perform any queries
        self::assertNotNull($provider->get($oemA, $b->sku, $factory));
        self::assertCount(0, $this->getQueryLog());

        // If value not found the new object should be created
        $spy     = Mockery::spy(static function () use ($oemB): ServiceGroup {
            return ServiceGroup::factory()->create([
                'oem_id' => $oemB,
                'sku'    => 'unKnown',
            ]);
        });
        $created = $provider->get($oemB, ' unKnown ', Closure::fromCallable($spy));

        $spy->shouldHaveBeenCalled();

        self::assertNotNull($created);
        self::assertEquals('unKnown', $created->sku);
        self::assertEquals($oemB->getKey(), $created->oem_id);
        self::assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // The created object should be in cache
        self::assertSame($created, $provider->get($oemB, ' unknown ', $factory));
        self::assertCount(0, $this->getQueryLog());

        // Created object should NOT be found
        $c = ServiceGroup::factory()->create([
            'oem_id' => $oemB,
        ]);

        $this->flushQueryLog();
        self::assertNull($provider->get($oemB, $c->sku));
        self::assertCount(0, $this->getQueryLog());
        $this->flushQueryLog();
    }
}
