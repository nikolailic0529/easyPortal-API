<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Data\Oem;
use App\Models\Data\ServiceGroup;
use App\Models\Data\ServiceLevel;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
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
        $factory = static function (?ServiceLevel $level): ServiceLevel {
            return $level ?? ServiceLevel::factory()->make();
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
        $queries  = $this->getQueryLog();
        $actual   = $provider->get($oemA, $groupA, ' a ', $factory);

        // Basic
        self::assertFalse($actual->wasRecentlyCreated);
        self::assertEquals('a', $actual->sku);
        self::assertEquals($a->name, $actual->name);
        self::assertEquals($oemA, $actual->oem);

        $queries->flush();

        // Second call should return same instance
        self::assertSame($actual, $provider->get($oemA, $groupA, 'a', $factory));
        self::assertSame($actual, $provider->get($oemA, $groupA, ' a ', $factory));
        self::assertSame($actual, $provider->get($oemA, $groupA, 'A', $factory));
        self::assertCount(0, $queries);

        self::assertNotSame($actual, $provider->get($oemB, $groupA, 'a', static function (): ServiceLevel {
            return ServiceLevel::factory()->make();
        }));

        $queries->flush();

        // All value should be loaded, so get() should not perform any queries
        $provider->get($oemA, $groupA, $b->sku, $factory);

        self::assertCount(0, $queries);

        $queries->flush();

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

        self::assertEquals('unKnown', $created->sku);
        self::assertEquals($oemB->getKey(), $created->oem_id);
        self::assertCount(1, $queries);

        $queries->flush();

        // The created object should be in cache
        self::assertSame($created, $provider->get($oemB, $groupB, ' unknown ', $factory));
        self::assertCount(0, $queries);

        // Created object should NOT be found
        $c = ServiceLevel::factory()->create([
            'oem_id'           => $oemB,
            'service_group_id' => $groupB,
        ]);

        $queries->flush();

        self::assertNull($provider->get($oemB, $groupB, $c->sku));
        self::assertCount(0, $queries);

        $queries->flush();
    }
}
