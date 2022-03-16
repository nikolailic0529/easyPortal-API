<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Oem;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Resolver\Resolvers\OemResolver
 */
class OemResolverTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::get
     */
    public function testGet(): void {
        // Prepare
        $factory = static function (): Oem {
            return Oem::factory()->make();
        };

        Oem::factory()->create(['key' => 'a']);
        Oem::factory()->create(['key' => 'b']);
        Oem::factory()->create(['key' => 'c']);

        // Run
        $provider = $this->app->make(OemResolver::class);
        $actual   = $provider->get('a', $factory);

        $this->flushQueryLog();

        // Basic
        self::assertNotNull($actual);
        self::assertEquals('a', $actual->key);

        // Second call should return same instance
        self::assertSame($actual, $provider->get('a', $factory));
        self::assertSame($actual, $provider->get(' a ', $factory));
        self::assertSame($actual, $provider->get('A', $factory));

        // All value should be loaded, so get() should not perform any queries
        self::assertNotNull($provider->get('b', $factory));
        self::assertCount(0, $this->getQueryLog());

        self::assertNotNull($provider->get('c', $factory));
        self::assertCount(0, $this->getQueryLog());

        // If value not found the new object should be created
        $spy     = Mockery::spy(static function (): Oem {
            return Oem::factory()->make([
                'key'  => 'unKnown',
                'name' => 'unKnown',
            ]);
        });
        $created = $provider->get(' unKnown ', Closure::fromCallable($spy));

        $spy->shouldHaveBeenCalled();

        self::assertNotNull($created);
        self::assertEquals('unKnown', $created->key);
        self::assertEquals('unKnown', $created->name);
        self::assertCount(0, $this->getQueryLog());

        $this->flushQueryLog();

        // The created object should be in cache
        self::assertSame($created, $provider->get('unknoWn', $factory));
        self::assertCount(0, $this->getQueryLog());

        // Created object should NOT be found
        $c = Oem::factory()->create();

        $this->flushQueryLog();
        self::assertNull($provider->get($c->key));
        self::assertCount(0, $this->getQueryLog());
        $this->flushQueryLog();
    }
}
