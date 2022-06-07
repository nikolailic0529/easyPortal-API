<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Oem;
use App\Models\ServiceGroup;
use App\Models\ServiceLevel;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\ServiceLevelResolver;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factory\Concerns\WithServiceLevel
 */
class WithServiceLevelTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::serviceLevel
     */
    public function testServiceLevelExistsThroughProvider(): void {
        $oem        = Oem::factory()->create();
        $group      = ServiceGroup::factory()->create([
            'oem_id' => $oem,
        ]);
        $level      = ServiceLevel::factory()->create([
            'oem_id'           => $oem,
            'service_group_id' => $group,
        ]);
        $normalizer = $this->app->make(Normalizer::class);
        $resolver   = $this->app->make(ServiceLevelResolver::class);
        $factory    = new class($normalizer, $resolver) extends Factory {
            use WithServiceLevel {
                serviceLevel as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected ServiceLevelResolver $serviceLevelResolver,
            ) {
                // empty
            }

            protected function getServiceLevelResolver(): ServiceLevelResolver {
                return $this->serviceLevelResolver;
            }
        };

        // If not - it should be created
        $queries = $this->getQueryLog();
        $created = $factory->serviceLevel($oem, $group, ' SKU ');

        self::assertNotNull($created);
        self::assertTrue($created->wasRecentlyCreated);
        self::assertEquals("{$group->getTranslatableKey()}/SKU", $created->key);
        self::assertEquals($oem->getKey(), $created->oem_id);
        self::assertEquals('SKU', $created->sku);
        self::assertEquals('SKU', $created->name);
        self::assertEquals('', $created->description);
        self::assertEquals($group->getKey(), $created->service_group_id);
        self::assertCount(2, $queries);

        $queries->flush();

        // If model exists - no action required
        self::assertEquals($level, $factory->serviceLevel($oem, $group, $level->sku));
        self::assertCount(0, $queries);

        $queries->flush();

        // Empty sku
        self::assertNull($factory->serviceLevel($oem, $group, ' '));
    }
}
