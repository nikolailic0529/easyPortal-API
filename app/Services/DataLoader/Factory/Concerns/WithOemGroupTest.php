<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\OemGroup;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Resolver\Resolvers\OemGroupResolver;
use App\Services\DataLoader\Schema\Type;
use App\Utils\Eloquent\Model;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factory\Concerns\WithOemGroup
 */
class WithOemGroupTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::oemGroup
     */
    public function testOemGroup(): void {
        // Prepare
        $resolver = $this->app->make(OemGroupResolver::class);
        $group    = OemGroup::factory()->create();
        $oem      = $group->oem;

        $factory = new class($resolver) extends Factory {
            use WithOemGroup {
                oemGroup as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected OemGroupResolver $oemGroupResolver,
            ) {
                // empty
            }

            public function getModel(): string {
                return Model::class;
            }

            public function create(Type $type): ?Model {
                return null;
            }

            protected function getOemGroupResolver(): OemGroupResolver {
                return $this->oemGroupResolver;
            }
        };

        // If model exists - no action required
        $queries = $this->getQueryLog()->flush();

        self::assertEquals(
            $group->withoutRelations(),
            $factory->oemGroup($oem, $group->key, $group->name)->withoutRelations(),
        );
        self::assertCount(1, $queries);

        // If not - it should be created
        $queries = $this->getQueryLog()->flush();
        $created = $factory->oemGroup($oem, 'newkey', ' New  Oem  Group  Name ');

        self::assertTrue($created->wasRecentlyCreated);
        self::assertEquals('newkey', $created->key);
        self::assertEquals('New Oem Group Name', $created->name);
        self::assertCount(2, $queries);
    }
}
