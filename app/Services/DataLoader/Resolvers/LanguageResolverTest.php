<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Language;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Resolvers\LanguageResolver
 */
class LanguageResolverTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::get
     */
    public function testGet(): void {
        // Prepare
        $factory = static function (): Language {
            return Language::factory()->make();
        };

        Language::factory()->create(['code' => 'a']);
        Language::factory()->create(['code' => 'b']);
        Language::factory()->create(['code' => 'c']);

        // Run
        $provider = $this->app->make(LanguageResolver::class);
        $actual   = $provider->get('a', $factory);

        $this->flushQueryLog();

        // Basic
        $this->assertNotNull($actual);
        $this->assertEquals('a', $actual->code);

        // Second call should return same instance
        $this->assertSame($actual, $provider->get('a', $factory));
        $this->assertSame($actual, $provider->get(' a ', $factory));
        $this->assertSame($actual, $provider->get('A', $factory));
        $this->assertCount(0, $this->getQueryLog());

        // All value should be loaded, so get() should not perform any queries
        $this->assertNotNull($provider->get('b', $factory));
        $this->assertCount(0, $this->getQueryLog());

        $this->assertNotNull($provider->get('c', $factory));
        $this->assertCount(0, $this->getQueryLog());

        // If value not found the new object should be created
        $spy     = Mockery::spy(static function (): Language {
            return Language::factory()->make([
                'code' => 'UN',
                'name' => 'unknown name',
            ]);
        });
        $created = $provider->get(' uN ', Closure::fromCallable($spy));

        $spy->shouldHaveBeenCalled();

        $this->assertNotNull($created);
        $this->assertEquals('UN', $created->code);
        $this->assertEquals('unknown name', $created->name);
        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // The created object should be in cache
        $this->assertSame($created, $provider->get('Un', $factory));
        $this->assertCount(0, $this->getQueryLog());

        // Created object should be found
        $c = Language::factory()->create();

        $this->flushQueryLog();
        $this->assertEquals($c->getKey(), $provider->get($c->getKey())?->getKey());
        $this->assertCount(1, $this->getQueryLog());
        $this->flushQueryLog();
    }
}
