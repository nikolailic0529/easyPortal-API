<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Language;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Resolver\Resolvers\LanguageResolver
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
        self::assertNotEmpty($actual);
        self::assertEquals('a', $actual->code);

        // Second call should return same instance
        self::assertSame($actual, $provider->get('a', $factory));
        self::assertSame($actual, $provider->get(' a ', $factory));
        self::assertSame($actual, $provider->get('A', $factory));
        self::assertCount(0, $this->getQueryLog());

        // All value should be loaded, so get() should not perform any queries
        self::assertNotEmpty($provider->get('b', $factory));
        self::assertCount(0, $this->getQueryLog());

        self::assertNotEmpty($provider->get('c', $factory));
        self::assertCount(0, $this->getQueryLog());

        // If value not found the new object should be created
        $spy     = Mockery::spy(static function (): Language {
            return Language::factory()->make([
                'code' => 'UN',
                'name' => 'unknown name',
            ]);
        });
        $created = $provider->get(' uN ', Closure::fromCallable($spy));

        $spy->shouldHaveBeenCalled();

        self::assertNotEmpty($created);
        self::assertEquals('UN', $created->code);
        self::assertEquals('unknown name', $created->name);
        self::assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // The created object should be in cache
        self::assertSame($created, $provider->get('Un', $factory));
        self::assertCount(0, $this->getQueryLog());

        // Created object should be found
        $c = Language::factory()->create();

        $this->flushQueryLog();
        self::assertEquals($c->getKey(), $provider->get($c->code)?->getKey());
        self::assertCount(1, $this->getQueryLog());
        $this->flushQueryLog();
    }
}
