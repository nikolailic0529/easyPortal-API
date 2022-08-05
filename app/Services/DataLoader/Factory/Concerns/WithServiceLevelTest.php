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
    public function testServiceLevel(): void {
        // Prepare
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

        // If model exists and not changed - no action required
        $queries = $this->getQueryLog()->flush();
        $actual  = $factory->serviceLevel($oem, $group, $level->sku, $level->name);

        self::assertEquals($level, $actual);
        self::assertCount(1, $queries);


        // If model exists and changed - it should not be updated
        $queries = $this->getQueryLog()->flush();
        $newName = $this->faker->sentence();
        $updated = $factory->serviceLevel($oem, $group, $level->sku, $newName);

        self::assertNotNull($updated);
        self::assertEquals($level, $updated);
        self::assertCount(0, $queries);

        $queries->flush();

        // If model exists and changed - empty `name` should be updated
        $level       = $updated;
        $level->name = '';
        $level->save();

        $queries = $this->getQueryLog()->flush();
        $newName = $this->faker->sentence();
        $updated = $factory->serviceLevel($oem, $group, $level->sku, $newName);

        self::assertNotNull($updated);
        self::assertEquals($newName, $updated->name);
        self::assertCount(1, $queries);

        // If model exists and changed - `name` = `sku` should be updated
        $level       = $updated;
        $level->name = $level->sku;
        $level->save();

        $queries = $this->getQueryLog()->flush();
        $newName = $this->faker->sentence();
        $updated = $factory->serviceLevel($oem, $group, $level->sku, $newName);

        self::assertNotNull($updated);
        self::assertEquals($newName, $updated->name);
        self::assertCount(1, $queries);

        // If not - it should be created
        $sku     = $this->faker->uuid();
        $name    = $this->faker->sentence();
        $queries = $this->getQueryLog()->flush();
        $created = $factory->serviceLevel($oem, $group, $sku, $name);

        self::assertNotNull($created);
        self::assertEquals($oem->getKey(), $created->oem_id);
        self::assertEquals($sku, $created->sku);
        self::assertEquals($name, $created->name);
        self::assertEquals('', $created->description);
        self::assertCount(1, $queries);

        // If not - it should be created (no `name`)
        $sku     = $this->faker->uuid();
        $queries = $this->getQueryLog()->flush();
        $created = $factory->serviceLevel($oem, $group, $sku);

        self::assertNotNull($created);
        self::assertEquals($oem->getKey(), $created->oem_id);
        self::assertEquals($sku, $created->sku);
        self::assertEquals($sku, $created->name);
        self::assertCount(1, $queries);
    }
}
