<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Data\ProductLine;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Resolver\Resolvers\ProductLineResolver;
use App\Services\DataLoader\Schema\Type;
use App\Utils\Eloquent\Model;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factory\Concerns\WithProductLine
 */
class WithProductLineTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::productLine
     */
    public function testProductLine(): void {
        $line     = ProductLine::factory()->create();
        $resolver = $this->app->make(ProductLineResolver::class);
        $factory  = new class($resolver) extends Factory {
            use WithProductLine {
                productLine as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected ProductLineResolver $productLineResolver,
            ) {
                // empty
            }

            protected function getProductLineResolver(): ProductLineResolver {
                return $this->productLineResolver;
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
        $created = $factory->productLine('KEY');

        self::assertNotNull($created);
        self::assertTrue($created->wasRecentlyCreated);
        self::assertEquals('KEY', $created->key);
        self::assertEquals('KEY', $created->name);
        self::assertCount(3, $queries);

        // If model exists - no action required
        $queries = $this->getQueryLog()->flush();

        self::assertEquals($line, $factory->productLine($line->key));
        self::assertCount(0, $queries);

        // Empty key
        self::assertNull($factory->productLine(''));
    }
}
