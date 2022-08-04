<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Oem;
use App\Models\ServiceGroup;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\ServiceGroupResolver;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factory\Concerns\WithServiceGroup
 */
class WithServiceGroupTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::serviceGroup
     */
    public function testServiceGroup(): void {
        // Prepare
        $oem        = Oem::factory()->create();
        $group      = ServiceGroup::factory()->create([
            'oem_id' => $oem,
        ]);
        $normalizer = $this->app->make(Normalizer::class);
        $resolver   = $this->app->make(ServiceGroupResolver::class);
        $factory    = new class($normalizer, $resolver) extends Factory {
            use WithServiceGroup {
                serviceGroup as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected ServiceGroupResolver $serviceGroupResolver,
            ) {
                // empty
            }

            protected function getServiceGroupResolver(): ServiceGroupResolver {
                return $this->serviceGroupResolver;
            }
        };

        // If model exists and not changed - no action required
        $queries = $this->getQueryLog()->flush();
        $actual  = $factory->serviceGroup($oem, $group->sku, $group->name);

        self::assertEquals($group, $actual);
        self::assertCount(1, $queries);


        // If model exists and changed - it should not be updated
        $queries = $this->getQueryLog()->flush();
        $newName = $this->faker->sentence();
        $updated = $factory->serviceGroup($oem, $group->sku, $newName);

        self::assertNotNull($updated);
        self::assertEquals($group, $updated);
        self::assertCount(0, $queries);

        $queries->flush();

        // If model exists and changed - empty `name` should be updated
        $group       = $updated;
        $group->name = '';
        $group->save();

        $queries = $this->getQueryLog()->flush();
        $newName = $this->faker->sentence();
        $updated = $factory->serviceGroup($oem, $group->sku, $newName);

        self::assertNotNull($updated);
        self::assertEquals($newName, $updated->name);
        self::assertCount(1, $queries);

        // If model exists and changed - `name` = `sku` should be updated
        $group       = $updated;
        $group->name = $group->sku;
        $group->save();

        $queries = $this->getQueryLog()->flush();
        $newName = $this->faker->sentence();
        $updated = $factory->serviceGroup($oem, $group->sku, $newName);

        self::assertNotNull($updated);
        self::assertEquals($newName, $updated->name);
        self::assertCount(1, $queries);

        // If not - it should be created
        $sku     = $this->faker->uuid();
        $name    = $this->faker->sentence();
        $queries = $this->getQueryLog()->flush();
        $created = $factory->serviceGroup($oem, $sku, $name);

        self::assertNotNull($created);
        self::assertEquals($oem->getKey(), $created->oem_id);
        self::assertEquals($sku, $created->sku);
        self::assertEquals($name, $created->name);
        self::assertCount(1, $queries);

        // If not - it should be created (no `name`)
        $sku     = $this->faker->uuid();
        $queries = $this->getQueryLog()->flush();
        $created = $factory->serviceGroup($oem, $sku);

        self::assertNotNull($created);
        self::assertEquals($oem->getKey(), $created->oem_id);
        self::assertEquals($sku, $created->sku);
        self::assertEquals($sku, $created->name);
        self::assertCount(1, $queries);
    }
}
