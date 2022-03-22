<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\City;
use App\Models\Country;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Resolver\Resolvers\ProductResolver
 */
class CityResolverTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::get
     */
    public function testGet(): void {
        // Prepare
        $countryA = Country::factory()->create();
        $countryB = Country::factory()->create();
        $factory  = static function (): City {
            return City::factory()->make();
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

        $this->flushQueryLog();

        // Basic
        self::assertNotNull($actual);
        self::assertFalse($actual->wasRecentlyCreated);
        self::assertEquals('a', $actual->key);
        self::assertEquals($countryA, $actual->country);

        $this->flushQueryLog();

        // Second call should return same instance
        self::assertSame($actual, $provider->get($countryA, 'a', $factory));
        self::assertSame($actual, $provider->get($countryA, ' a ', $factory));
        self::assertSame($actual, $provider->get($countryA, 'A', $factory));
        self::assertCount(0, $this->getQueryLog());

        self::assertNotSame($actual, $provider->get($countryA, 'b', $factory));

        $this->flushQueryLog();

        // All value should be loaded, so get() should not perform any queries
        self::assertNotNull($provider->get($countryA, 'b', $factory));
        self::assertCount(0, $this->getQueryLog());

        self::assertNotNull($provider->get($countryA, 'c', $factory));
        self::assertCount(0, $this->getQueryLog());

        // If value not found the new object should be created
        $spy     = Mockery::spy(static function () use ($countryB): City {
            return City::factory()->make([
                'key'        => 'unKnown',
                'country_id' => $countryB,
            ]);
        });
        $created = $provider->get($countryB, ' unKnown ', Closure::fromCallable($spy));

        $spy->shouldHaveBeenCalled();

        self::assertNotNull($created);
        self::assertEquals('unKnown', $created->key);
        self::assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // The created object should be in cache
        self::assertSame($created, $provider->get($countryB, 'unknoWn', $factory));
        self::assertCount(0, $this->getQueryLog());

        // Created object should be found
        $c = City::factory()->create([
            'country_id' => $countryA,
        ]);

        $this->flushQueryLog();
        self::assertEquals($c->getKey(), $provider->get($countryA, $c->key)?->getKey());
        self::assertCount(1, $this->getQueryLog());
        $this->flushQueryLog();
    }
}
