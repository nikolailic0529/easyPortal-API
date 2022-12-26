<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Data\ProductGroup;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\ProductGroupResolver;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factory\Concerns\WithProductGroup
 */
class WithProductGroupTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::productGroup
     */
    public function testProductGroup(): void {
        $group      = ProductGroup::factory()->create();
        $normalizer = $this->app->make(Normalizer::class);
        $resolver   = $this->app->make(ProductGroupResolver::class);
        $factory    = new class($normalizer, $resolver) extends Factory {
            use WithProductGroup {
                productGroup as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected ProductGroupResolver $productGroupResolver,
            ) {
                // empty
            }

            protected function getProductGroupResolver(): ProductGroupResolver {
                return $this->productGroupResolver;
            }
        };

        // If not - it should be created
        $queries = $this->getQueryLog()->flush();
        $created = $factory->productGroup('KEY');

        self::assertNotNull($created);
        self::assertTrue($created->wasRecentlyCreated);
        self::assertEquals('KEY', $created->key);
        self::assertEquals('KEY', $created->name);
        self::assertCount(3, $queries);

        // If model exists - no action required
        $queries = $this->getQueryLog()->flush();

        self::assertEquals($group, $factory->productGroup($group->key));
        self::assertCount(0, $queries);

        // Empty key
        self::assertNull($factory->productGroup(''));
    }
}
