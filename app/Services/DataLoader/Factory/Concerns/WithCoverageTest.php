<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Data\Coverage;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Resolver\Resolvers\CoverageResolver;
use App\Services\DataLoader\Schema\Type;
use App\Utils\Eloquent\Model;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factory\Concerns\WithCoverage
 */
class WithCoverageTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::coverage
     */
    public function testOem(): void {
        // Prepare
        $resolver = $this->app->make(CoverageResolver::class);
        $coverage = Coverage::factory()->create();
        $factory  = new class($resolver) extends Factory {
            use WithCoverage {
                coverage as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected CoverageResolver $coverageResolver,
            ) {
                // empty
            }

            protected function getCoverageResolver(): CoverageResolver {
                return $this->coverageResolver;
            }

            public function getModel(): string {
                return Model::class;
            }

            public function create(Type $type, bool $force = false): ?Model {
                return null;
            }
        };

        // If model exists - no action required
        $queries = $this->getQueryLog()->flush();

        self::assertEquals($coverage, $factory->coverage($coverage->key));
        self::assertCount(1, $queries);

        // If not - it should be created
        $queries = $this->getQueryLog()->flush();
        $created = $factory->coverage('new');

        self::assertTrue($created->wasRecentlyCreated);
        self::assertEquals('new', $created->key);
        self::assertEquals('New', $created->name);
        self::assertCount(2, $queries);
    }
}
