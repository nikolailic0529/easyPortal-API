<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Data\ProductLine;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\DataLoader\Resolver\Resolvers\ProductLineResolver
 */
class ProductLineResolverTest extends TestCase {
    use WithQueryLog;

    public function testGet(): void {
        // Prepare
        $factory = static function (?ProductLine $line): ProductLine {
            return $line ?? ProductLine::factory()->make();
        };

        $a = ProductLine::factory()->create([
            'key' => 'a',
        ]);
        ProductLine::factory()->create([
            'key' => 'b',
        ]);
        ProductLine::factory()->create([
            'key' => 'c',
        ]);

        // Run
        $provider = $this->app->make(ProductLineResolver::class);
        $actual   = $provider->get(' a ', $factory);

        // Basic
        self::assertNotEmpty($actual);
        self::assertFalse($actual->wasRecentlyCreated);
        self::assertEquals('a', $actual->key);
        self::assertEquals($a->name, $actual->name);

        // Second call should return same instance
        $queries = $this->getQueryLog()->flush();

        self::assertSame($actual, $provider->get('a', $factory));
        self::assertSame($actual, $provider->get(' a ', $factory));
        self::assertSame($actual, $provider->get('A', $factory));
        self::assertCount(0, $queries);

        // All value should be loaded, so get() should not perform any queries
        $queries = $this->getQueryLog()->flush();

        $provider->get('b', $factory);

        self::assertCount(0, $queries);

        // Should be found in DB
        $queries = $this->getQueryLog()->flush();

        self::assertNotEmpty($provider->get('c', $factory));
        self::assertCount(0, $queries);

        // If not, the new object should be created
        $spy     = Mockery::spy(static function (): ProductLine {
            return ProductLine::factory()->create([
                'key' => 'unKnown',
            ]);
        });
        $queries = $this->getQueryLog()->flush();
        $created = $provider->get(' unKnown ', Closure::fromCallable($spy));

        $spy->shouldHaveBeenCalled();

        self::assertNotEmpty($created);
        self::assertEquals('unKnown', $created->key);
        self::assertCount(2, $queries);

        // The created object should be in cache
        $queries = $this->getQueryLog()->flush();

        self::assertSame($created, $provider->get(' unknown ', $factory));
        self::assertCount(0, $queries);
    }
}
