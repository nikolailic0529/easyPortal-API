<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Data\City;
use App\Models\Data\Country;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\DataLoader\Resolver\Resolvers\CityResolver
 */
class CityResolverTest extends TestCase {
    use WithQueryLog;

    public function testGet(): void {
        // Prepare
        $countryA = Country::factory()->create();
        $countryB = Country::factory()->create();
        $factory  = static function (?City $city): City {
            return $city ?? City::factory()->make();
        };

        City::factory()->create([
            'country_id' => $countryA,
            'key'        => 'a',
        ]);
        City::factory()->create([
            'country_id' => $countryA,
            'key'        => 'b',
        ]);
        City::factory()->create([
            'country_id' => $countryA,
            'key'        => 'c',
        ]);

        // Run
        $provider = $this->app->make(CityResolver::class);
        $actual   = $provider->get($countryA, 'a', $factory);

        // Basic
        self::assertNotEmpty($actual);
        self::assertFalse($actual->wasRecentlyCreated);
        self::assertEquals('a', $actual->key);
        self::assertEquals($countryA, $actual->country);

        // Second call should return same instance
        $queries = $this->getQueryLog()->flush();

        self::assertSame($actual, $provider->get($countryA, 'a', $factory));
        self::assertSame($actual, $provider->get($countryA, ' a ', $factory));
        self::assertSame($actual, $provider->get($countryA, 'A', $factory));
        self::assertCount(0, $queries);

        self::assertNotSame($actual, $provider->get($countryA, 'b', $factory));

        // All value should be loaded, so get() should not perform any queries
        $queries = $this->getQueryLog()->flush();

        self::assertNotEmpty($provider->get($countryA, 'b', $factory));
        self::assertCount(0, $queries);

        self::assertNotEmpty($provider->get($countryA, 'c', $factory));
        self::assertCount(0, $queries);

        // If value not found the new object should be created
        $spy     = Mockery::spy(static function () use ($countryB): City {
            return City::factory()->make([
                'key'        => 'unKnown',
                'country_id' => $countryB,
            ]);
        });
        $queries = $this->getQueryLog()->flush();
        $created = $provider->get($countryB, ' unKnown ', Closure::fromCallable($spy));

        $spy->shouldHaveBeenCalled();

        self::assertNotEmpty($created);
        self::assertEquals('unKnown', $created->key);
        self::assertCount(1, $queries);

        // The created object should be in cache
        $queries = $this->getQueryLog()->flush();

        self::assertSame($created, $provider->get($countryB, 'unknoWn', $factory));
        self::assertCount(0, $queries);

        // Created object should be found
        $c       = City::factory()->create([
            'country_id' => $countryA,
        ]);
        $queries = $this->getQueryLog()->flush();

        self::assertEquals($c->getKey(), $provider->get($countryA, $c->key)?->getKey());
        self::assertCount(1, $queries);
    }
}
