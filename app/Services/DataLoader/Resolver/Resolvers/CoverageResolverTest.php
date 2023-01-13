<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Data\Coverage;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\DataLoader\Resolver\Resolvers\CoverageResolver
 */
class CoverageResolverTest extends TestCase {
    use WithQueryLog;

    public function testGet(): void {
        // Prepare
        $factory = static function (?Coverage $coverage): Coverage {
            return $coverage ?? Coverage::factory()->make();
        };

        Coverage::factory()->create(['key' => 'a']);
        Coverage::factory()->create(['key' => 'b']);
        Coverage::factory()->create(['key' => 'c']);

        // Run
        $provider = $this->app->make(CoverageResolver::class);
        $actual   = $provider->get('a', $factory);

        // Basic
        self::assertNotEmpty($actual);
        self::assertEquals('a', $actual->key);

        // Second call should return same instance
        $queries = $this->getQueryLog()->flush();

        self::assertSame($actual, $provider->get('a', $factory));
        self::assertSame($actual, $provider->get(' a ', $factory));
        self::assertSame($actual, $provider->get('A', $factory));
        self::assertCount(0, $queries);

        // All value should be loaded, so get() should not perform any queries
        $queries = $this->getQueryLog()->flush();

        self::assertNotEmpty($provider->get('b', $factory));
        self::assertCount(0, $queries);

        self::assertNotEmpty($provider->get('c', $factory));
        self::assertCount(0, $queries);

        // If value not found the new object should be created
        $spy     = Mockery::spy(static function (): Coverage {
            return Coverage::factory()->make([
                'key'  => 'UN',
                'name' => 'unknown name',
            ]);
        });
        $queries = $this->getQueryLog()->flush();
        $created = $provider->get(' uN ', Closure::fromCallable($spy));

        $spy->shouldHaveBeenCalled();

        self::assertNotEmpty($created);
        self::assertEquals('UN', $created->key);
        self::assertEquals('unknown name', $created->name);
        self::assertCount(1, $queries);

        // The created object should be in cache
        $queries = $this->getQueryLog()->flush();

        self::assertSame($created, $provider->get('Un', $factory));
        self::assertCount(0, $queries);

        // Created object should be found
        $c       = Coverage::factory()->create();
        $queries = $this->getQueryLog()->flush();

        self::assertEquals($c->getKey(), $provider->get($c->key)?->getKey());
        self::assertCount(1, $queries);
    }
}
