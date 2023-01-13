<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Data\Currency;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Resolver\Resolvers\CurrencyResolver
 */
class CurrencyResolverTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::get
     */
    public function testGet(): void {
        // Prepare
        $factory = static function (?Currency $currency): Currency {
            return $currency ?? Currency::factory()->make();
        };

        Currency::factory()->create(['code' => 'a']);
        Currency::factory()->create(['code' => 'b']);
        Currency::factory()->create(['code' => 'c']);

        // Run
        $provider = $this->app->make(CurrencyResolver::class);
        $actual   = $provider->get('a', $factory);
        $queries  = $this->getQueryLog();

        $queries->flush();

        // Basic
        self::assertEquals('a', $actual->code);

        // Second call should return same instance
        self::assertSame($actual, $provider->get('a', $factory));
        self::assertSame($actual, $provider->get(' a ', $factory));
        self::assertSame($actual, $provider->get('A', $factory));
        self::assertCount(0, $queries);

        // All value should be loaded, so get() should not perform any queries
        $provider->get('b', $factory);

        self::assertCount(0, $queries);

        $provider->get('c', $factory);

        self::assertCount(0, $queries);

        // If value not found the new object should be created
        $spy     = Mockery::spy(static function (): Currency {
            return Currency::factory()->make([
                'code' => 'UN',
                'name' => 'unknown name',
            ]);
        });
        $created = $provider->get(' uN ', Closure::fromCallable($spy));

        $spy->shouldHaveBeenCalled();

        self::assertEquals('UN', $created->code);
        self::assertEquals('unknown name', $created->name);
        self::assertCount(1, $queries);

        $queries->flush();

        // The created object should be in cache
        self::assertSame($created, $provider->get('Un', $factory));
        self::assertCount(0, $queries);

        // Created object should be found
        $c = Currency::factory()->create();

        $queries->flush();

        self::assertEquals($c->getKey(), $provider->get($c->code)?->getKey());
        self::assertCount(1, $queries);

        $queries->flush();
    }
}
