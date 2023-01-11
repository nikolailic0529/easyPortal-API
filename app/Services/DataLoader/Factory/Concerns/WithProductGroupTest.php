<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Data\ProductGroup;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Resolver\Resolvers\ProductGroupResolver;
use App\Services\DataLoader\Schema\Type;
use App\Utils\Eloquent\Model;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\DataLoader\Factory\Concerns\WithProductGroup
 */
class WithProductGroupTest extends TestCase {
    use WithQueryLog;

    public function testProductGroup(): void {
        $group    = ProductGroup::factory()->create();
        $resolver = $this->app->make(ProductGroupResolver::class);
        $factory  = new class($resolver) extends Factory {
            use WithProductGroup {
                productGroup as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected ProductGroupResolver $productGroupResolver,
            ) {
                // empty
            }

            protected function getProductGroupResolver(): ProductGroupResolver {
                return $this->productGroupResolver;
            }

            public function create(Type $type, bool $force = false): ?Model {
                return null;
            }

            public function getModel(): string {
                return Model::class;
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
