<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Model;
use App\Models\OemGroup;
use App\Services\DataLoader\Factories\ModelFactory;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\OemGroupResolver;
use App\Services\DataLoader\Schema\Type;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factories\Concerns\WithOemGroup
 */
class WithOemGroupTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::oemGroup
     */
    public function testOemGroup(): void {
        // Prepare
        $normalizer = $this->app->make(Normalizer::class);
        $resolver   = $this->app->make(OemGroupResolver::class);
        $group      = OemGroup::factory()->create();
        $oem        = $group->oem;

        $factory = new class($normalizer, $resolver) extends ModelFactory {
            use WithOemGroup {
                oemGroup as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected OemGroupResolver $oemGroupResolver,
            ) {
                // empty
            }

            public function create(Type $type): ?Model {
                return null;
            }

            protected function getOemGroupResolver(): OemGroupResolver {
                return $this->oemGroupResolver;
            }
        };

        $this->flushQueryLog();

        // If model exists - no action required
        $this->assertEquals(
            $group->withoutRelations(),
            $factory->oemGroup($oem, $group->key, $group->name)->withoutRelations(),
        );
        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // If not - it should be created
        $created = $factory->oemGroup($oem, 'newkey', ' New  Oem  Group  Name ');

        $this->assertNotNull($created);
        $this->assertTrue($created->wasRecentlyCreated);
        $this->assertEquals('newkey', $created->key);
        $this->assertEquals('New Oem Group Name', $created->name);
        $this->assertCount(2, $this->getQueryLog());
    }
}
