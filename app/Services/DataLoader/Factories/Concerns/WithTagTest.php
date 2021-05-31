<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Model;
use App\Models\Tag;
use App\Services\DataLoader\Factories\DependentModelFactory;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\TagResolver;
use App\Services\DataLoader\Schema\Type;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factories\Concerns\WithTag
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
            public function __construct(Normalizer $normalizer, TagResolver $resolver) {
                $this->normalizer = $normalizer;
                $this->tags       = $resolver;
            }

            public function create(Model $object, Type $type): ?Model {
                return null;
            }
        };

        $this->flushQueryLog();

        // If model exists - no action required
        $this->assertEquals($tag, $factory->tag($tag->name));
        $this->assertCount(1, $this->getQueryLog());

        // If not - it should be created
        $created = $factory->tag(' New  Tag ');

        $this->assertNotNull($created);
        $this->assertTrue($created->wasRecentlyCreated);
        $this->assertEquals('New Tag', $created->name);
    }
}