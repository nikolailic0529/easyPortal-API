<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Tag;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
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
        $factory = static function (): Tag {
            return Tag::factory()->make();
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

        $this->flushQueryLog();

        // Basic
        self::assertNotNull($actual);
        self::assertFalse($actual->wasRecentlyCreated);
        self::assertEquals('a', $actual->name);

        $this->flushQueryLog();

        // Second call should return same instance
        self::assertSame($actual, $provider->get(' a ', $factory));
        self::assertCount(0, $this->getQueryLog());

        self::assertNotSame($actual, $provider->get('name', static function (): Tag {
            return Tag::factory()->make();
        }));

        $this->flushQueryLog();

        // If not, the new object should be created
        $spy     = Mockery::spy(static function (): Tag {
            return Tag::factory()->make([
                'name' => 'unKnown',
            ]);
        });
        $created = $provider->get(' unKnown ', Closure::fromCallable($spy));

        $spy->shouldHaveBeenCalled();

        self::assertNotNull($created);
        self::assertEquals('unKnown', $created->name);
        self::assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // The created object should be in cache
        self::assertSame($created, $provider->get(' unknown ', $factory));
        self::assertCount(0, $this->getQueryLog());

        // Created object should be found
        $c = Tag::factory()->create();

        $this->flushQueryLog();
        self::assertEquals($c->getKey(), $provider->get($c->name)?->getKey());
        self::assertCount(1, $this->getQueryLog());
        $this->flushQueryLog();
    }
}
