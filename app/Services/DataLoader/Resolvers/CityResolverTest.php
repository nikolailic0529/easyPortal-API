<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\City;
use App\Models\Country;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Resolvers\ProductResolver
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
            'name'       => 'a',
        ]);
        City::factory()->create([
            'country_id' => $countryA,
            'name'       => 'b',
        ]);
        City::factory()->create([
            'country_id' => $countryA,
            'name'       => 'c',
        ]);

        // Run
        $provider = $this->app->make(CityResolver::class);
        $actual   = $provider->get($countryA, 'a', $factory);

        $this->flushQueryLog();

        // Basic
        $this->assertNotNull($actual);
        $this->assertFalse($actual->wasRecentlyCreated);
        $this->assertEquals('a', $actual->name);
        $this->assertEquals($countryA, $actual->country);

        $this->flushQueryLog();

        // Second call should return same instance
        $this->assertSame($actual, $provider->get($countryA, 'a', $factory));
        $this->assertSame($actual, $provider->get($countryA, ' a ', $factory));
        $this->assertSame($actual, $provider->get($countryA, 'A', $factory));
        $this->assertCount(0, $this->getQueryLog());

        $this->assertNotSame($actual, $provider->get($countryA, 'b', $factory));

        $this->flushQueryLog();

        // All value should be loaded, so get() should not perform any queries
        $this->assertNotNull($provider->get($countryA, 'b', $factory));
        $this->assertCount(0, $this->getQueryLog());

        $this->assertNotNull($provider->get($countryA, 'c', $factory));
        $this->assertCount(0, $this->getQueryLog());

        // If value not found the new object should be created
        $spy     = Mockery::spy(static function () use ($countryB): City {
            return City::factory()->make([
                'name'       => 'unKnown',
                'country_id' => $countryB,
            ]);
        });
        $created = $provider->get($countryB, ' unKnown ', Closure::fromCallable($spy));

        $spy->shouldHaveBeenCalled();

        $this->assertNotNull($created);
        $this->assertEquals('unKnown', $created->name);
        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // The created object should be in cache
        $this->assertSame($created, $provider->get($countryB, 'unknoWn', $factory));
        $this->assertCount(0, $this->getQueryLog());

        // Created object should be found
        $c = City::factory()->create([
            'country_id' => $countryA,
        ]);

        $this->flushQueryLog();
        $this->assertEquals($c->getKey(), $provider->get($countryA, $c->name)?->getKey());
        $this->assertCount(1, $this->getQueryLog());
        $this->flushQueryLog();
    }
}
