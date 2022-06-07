<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Oem;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\OemResolver;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factory\Concerns\WithOem
 */
class WithOemTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::oem
     */
    public function testOemExistsThroughProvider(): void {
        $oem        = Oem::factory()->create();
        $normalizer = $this->app->make(Normalizer::class);
        $resolver   = $this->app->make(OemResolver::class);
        $factory    = new class($normalizer, $resolver) extends Factory {
            use WithOem {
                oem as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected OemResolver $oemResolver,
            ) {
                // empty
            }

            protected function getOemResolver(): OemResolver {
                return $this->oemResolver;
            }
        };

        // If not - it should be created
        $queries = $this->getQueryLog();
        $created = $factory->oem(' SKU ');

        self::assertNotNull($created);
        self::assertTrue($created->wasRecentlyCreated);
        self::assertEquals('SKU', $created->key);
        self::assertEquals('SKU', $created->name);
        self::assertCount(2, $queries);

        $queries->flush();

        // If model exists - no action required
        self::assertEquals($oem, $factory->oem($oem->key));
        self::assertCount(0, $queries);

        $queries->flush();

        // Empty sku
        self::assertNull($factory->oem(' '));
    }
}
