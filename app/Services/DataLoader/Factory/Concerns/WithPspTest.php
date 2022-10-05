<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Data\Psp;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\PspResolver;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factory\Concerns\WithPsp
 */
class WithPspTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::psp
     */
    public function testPsp(): void {
        // Prepare
        $psp        = Psp::factory()->create();
        $normalizer = $this->app->make(Normalizer::class);
        $resolver   = $this->app->make(PspResolver::class);
        $factory    = new class($normalizer, $resolver) extends Factory {
            use WithPsp {
                psp as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected PspResolver $pspResolver,
            ) {
                // empty
            }

            protected function getPspResolver(): PspResolver {
                return $this->pspResolver;
            }
        };

        // If model exists and not changed - no action required
        $queries = $this->getQueryLog()->flush();
        $actual  = $factory->psp($psp->key, $psp->name);

        self::assertEquals($psp, $actual);
        self::assertCount(1, $queries);

        // If model exists and changed - it should not be updated
        $queries = $this->getQueryLog()->flush();
        $newName = $this->faker->sentence();
        $updated = $factory->psp($psp->key, $newName);

        self::assertNotNull($updated);
        self::assertEquals($psp, $updated);
        self::assertCount(0, $queries);

        $queries->flush();

        // If model exists and changed - empty `name` should be updated
        $psp       = $updated;
        $psp->name = '';
        $psp->save();

        $queries = $this->getQueryLog()->flush();
        $newName = $this->faker->sentence();
        $updated = $factory->psp($psp->key, $newName);

        self::assertNotNull($updated);
        self::assertEquals($newName, $updated->name);
        self::assertCount(1, $queries);

        // If model exists and changed - `name` = `key` should be updated
        $psp       = $updated;
        $psp->name = $psp->key;
        $psp->save();

        $queries = $this->getQueryLog()->flush();
        $newName = $this->faker->sentence();
        $updated = $factory->psp($psp->key, $newName);

        self::assertNotNull($updated);
        self::assertEquals($newName, $updated->name);
        self::assertCount(1, $queries);

        // If not - it should be created
        $key     = $this->faker->uuid();
        $name    = $this->faker->sentence();
        $queries = $this->getQueryLog()->flush();
        $created = $factory->psp($key, $name);

        self::assertNotNull($created);
        self::assertEquals($key, $created->key);
        self::assertEquals($name, $created->name);
        self::assertCount(2, $queries);

        // If not - it should be created (no `name`)
        $key     = $this->faker->uuid();
        $queries = $this->getQueryLog()->flush();
        $created = $factory->psp($key);

        self::assertNotNull($created);
        self::assertEquals($key, $created->key);
        self::assertEquals($key, $created->name);
        self::assertCount(2, $queries);

        // Empty key
        self::assertNull($factory->psp(' '));
    }
}
