<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Data\Oem;
use App\Models\Data\ServiceGroup;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Resolver\Resolvers\ServiceGroupResolver;
use App\Services\DataLoader\Schema\Type;
use App\Utils\Eloquent\Model;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\DataLoader\Factory\Concerns\WithServiceGroup
 */
class WithServiceGroupTest extends TestCase {
    use WithQueryLog;

    public function testServiceGroup(): void {
        // Prepare
        $oem      = Oem::factory()->create();
        $group    = ServiceGroup::factory()->create([
            'oem_id' => $oem,
        ]);
        $resolver = $this->app->make(ServiceGroupResolver::class);
        $factory  = new class($resolver) extends Factory {
            use WithServiceGroup {
                serviceGroup as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected ServiceGroupResolver $serviceGroupResolver,
            ) {
                // empty
            }

            protected function getServiceGroupResolver(): ServiceGroupResolver {
                return $this->serviceGroupResolver;
            }

            public function create(Type $type, bool $force = false): ?Model {
                return null;
            }

            public function getModel(): string {
                return Model::class;
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
