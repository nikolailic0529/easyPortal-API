<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Data\Tag;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Resolver\Resolvers\TagResolver
 */
class TagResolverTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::get
     */
    public function testGet(): void {
        // Prepare
        $factory = static function (?Tag $tag): Tag {
            return $tag ?? Tag::factory()->make();
        };

        Tag::factory()->create([
            'name' => 'a',
        ]);
        Tag::factory()->create([
            'name' => 'b',
        ]);

        // Run
        $provider = $this->app->make(TagResolver::class);
        $actual   = $provider->get('a', $factory);

        // Basic
        self::assertNotEmpty($actual);
        self::assertFalse($actual->wasRecentlyCreated);
        self::assertEquals('a', $actual->name);

        // Second call should return same instance
        $queries = $this->getQueryLog()->flush();

        self::assertSame($actual, $provider->get(' a ', $factory));
        self::assertCount(0, $queries);

        self::assertNotSame($actual, $provider->get('name', static function (): Tag {
            return Tag::factory()->make();
        }));

        // If not, the new object should be created
        $spy     = Mockery::spy(static function (): Tag {
            return Tag::factory()->make([
                'name' => 'unKnown',
            ]);
        });
        $queries = $this->getQueryLog()->flush();
        $created = $provider->get(' unKnown ', Closure::fromCallable($spy));

        $spy->shouldHaveBeenCalled();

        self::assertNotEmpty($created);
        self::assertEquals('unKnown', $created->name);
        self::assertCount(1, $queries);

        // The created object should be in cache
        $queries = $this->getQueryLog()->flush();

        self::assertSame($created, $provider->get(' unknown ', $factory));
        self::assertCount(0, $queries);

        // Created object should be found
        $c       = Tag::factory()->create();
        $queries = $this->getQueryLog()->flush();

        self::assertEquals($c->getKey(), $provider->get($c->name)?->getKey());
        self::assertCount(1, $queries);
    }
}
