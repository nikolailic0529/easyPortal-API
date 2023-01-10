<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Data\Tag;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Resolver\Resolvers\TagResolver;
use App\Services\DataLoader\Schema\Type;
use App\Utils\Eloquent\Model;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factory\Concerns\WithTag
 */
class WithTagTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::tag
     */
    public function testTag(): void {
        // Prepare
        $resolver = $this->app->make(TagResolver::class);
        $tag      = Tag::factory()->create();

        $factory = new class($resolver) extends Factory {
            use WithTag {
                tag as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected TagResolver $tagResolver,
            ) {
                // empty
            }

            public function create(Type $type): ?Model {
                return null;
            }

            public function getModel(): string {
                return Model::class;
            }

            protected function getTagResolver(): TagResolver {
                return $this->tagResolver;
            }
        };

        // If model exists - no action required
        $queries = $this->getQueryLog()->flush();

        self::assertEquals($tag, $factory->tag($tag->name));
        self::assertCount(1, $queries);

        // If not - it should be created
        $created = $factory->tag('New Tag');

        self::assertTrue($created->wasRecentlyCreated);
        self::assertEquals('New Tag', $created->name);
    }
}
