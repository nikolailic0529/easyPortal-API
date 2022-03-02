<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Coverage;
use App\Services\DataLoader\Factory\ModelFactory;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\CoverageResolver;
use App\Services\DataLoader\Schema\Type;
use App\Utils\Eloquent\Model;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factory\Concerns\WithCoverage
 */
class WithCoverageTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::status
     */
    public function testOem(): void {
        // Prepare
        $normalizer = $this->app->make(Normalizer::class);
        $resolver   = $this->app->make(CoverageResolver::class);
        $coverage   = Coverage::factory()->create();
        $factory    = new class($normalizer, $resolver) extends ModelFactory {
            use WithCoverage {
                coverage as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected CoverageResolver $coverageResolver,
            ) {
                // empty
            }

            protected function getNormalizer(): Normalizer {
                return $this->normalizer;
            }

            protected function getCoverageResolver(): CoverageResolver {
                return $this->coverageResolver;
            }

            public function create(Type $type): ?Model {
                return null;
            }
        };

        $this->flushQueryLog();

        // If model exists - no action required
        $this->assertEquals($coverage, $factory->coverage($coverage->key));
        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // If not - it should be created
        $created = $factory->coverage('new ');

        $this->assertNotNull($created);
        $this->assertTrue($created->wasRecentlyCreated);
        $this->assertEquals('new', $created->key);
        $this->assertEquals('New', $created->name);
        $this->assertCount(2, $this->getQueryLog());
    }
}
