<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Tag;
use App\Services\DataLoader\Factory\DependentModelFactory;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\TagResolver;
use App\Services\DataLoader\Schema\Type;
use App\Utils\Eloquent\Model;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
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
        $normalizer = $this->app->make(Normalizer::class);
        $resolver   = $this->app->make(TagResolver::class);
        $tag        = Tag::factory()->create();

        $factory = new class($normalizer, $resolver) extends DependentModelFactory {
            use WithTag {
                tag as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected TagResolver $tagResolver,
            ) {
                // empty
            }

            public function create(Model $object, Type $type): ?Model {
                return null;
            }

            protected function getTagResolver(): TagResolver {
                return $this->tagResolver;
            }
        };

        $this->flushQueryLog();

        // If model exists - no action required
        self::assertEquals($tag, $factory->tag($tag->name));
        self::assertCount(1, $this->getQueryLog());

        // If not - it should be created
        $created = $factory->tag(' New  Tag ');

        self::assertNotNull($created);
        self::assertTrue($created->wasRecentlyCreated);
        self::assertEquals('New Tag', $created->name);
    }
}
