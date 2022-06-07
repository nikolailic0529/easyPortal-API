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
    public function testServiceGroupExistsThroughProvider(): void {
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

        // If not - it should be created
        $queries = $this->getQueryLog();
        $created = $factory->serviceGroup($oem, ' SKU ');

        self::assertNotNull($created);
        self::assertTrue($created->wasRecentlyCreated);
        self::assertEquals("{$oem->getTranslatableKey()}/SKU", $created->key);
        self::assertEquals($oem->getKey(), $created->oem_id);
        self::assertEquals('SKU', $created->sku);
        self::assertEquals('SKU', $created->name);
        self::assertCount(2, $queries);

        $queries->flush();

        // If model exists - no action required
        self::assertEquals($group, $factory->serviceGroup($oem, $group->sku));
        self::assertCount(0, $queries);

        $queries->flush();

        // Empty sku
        self::assertNull($factory->serviceGroup($oem, ' '));
    }
}
