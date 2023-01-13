<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Data\Country;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\DataLoader\Resolver\Resolvers\CountryResolver
 */
class CountryResolverTest extends TestCase {
    use WithQueryLog;

    public function testGet(): void {
        // Prepare
        $factory = static function (?Country $country): Country {
            return $country ?? Country::factory()->make();
        };

        Country::factory()->create(['code' => 'a']);
        Country::factory()->create(['code' => 'b']);
        Country::factory()->create(['code' => 'c']);

        // Run
        $provider = $this->app->make(CountryResolver::class);
        $actual   = $provider->get('a', $factory);

        // Basic
        self::assertNotEmpty($actual);
        self::assertEquals('a', $actual->code);

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
        $spy     = Mockery::spy(static function (): Country {
            return Country::factory()->make([
                'code' => 'UN',
                'name' => 'unknown name',
            ]);
        });
        $queries = $this->getQueryLog()->flush();
        $created = $provider->get(' uN ', Closure::fromCallable($spy));

        $spy->shouldHaveBeenCalled();

        self::assertNotEmpty($created);
        self::assertEquals('UN', $created->code);
        self::assertEquals('unknown name', $created->name);
        self::assertCount(1, $queries);

        // The created object should be in cache
        $queries = $this->getQueryLog()->flush();

        self::assertSame($created, $provider->get('Un', $factory));
        self::assertCount(0, $queries);

        // Created object should be found
        $c       = Country::factory()->create();
        $queries = $this->getQueryLog()->flush();

        self::assertEquals($c->getKey(), $provider->get($c->code)?->getKey());
        self::assertCount(1, $queries);
    }
}
