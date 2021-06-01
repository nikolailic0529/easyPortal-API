<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Tag;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Resolvers\TagResolver
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
        $this->assertNotNull($actual);
        $this->assertFalse($actual->wasRecentlyCreated);
        $this->assertEquals('a', $actual->name);

        $this->flushQueryLog();

        // Second call should return same instance
        $this->assertSame($actual, $provider->get(' a ', $factory));
        $this->assertCount(0, $this->getQueryLog());

        $this->assertNotSame($actual, $provider->get('name', static function (): Tag {
            return Tag::factory()->make();
        }));

        $this->flushQueryLog();

        // If not, the new object should be created
        $spy     = Mockery::spy(static function (): Tag {
            return Tag::factory()->create([
                'name' => 'unKnown',
            ]);
        });
        $created = $provider->get(' unKnown ', Closure::fromCallable($spy));

        $spy->shouldHaveBeenCalled();

        $this->assertNotNull($created);
        $this->assertEquals('unKnown', $created->name);
        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // The created object should be in cache
        $this->assertSame($created, $provider->get(' unknown ', $factory));
        $this->assertCount(0, $this->getQueryLog());
    }
}
