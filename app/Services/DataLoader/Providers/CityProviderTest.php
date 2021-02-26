<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Providers;

use App\Models\City;
use App\Models\Country;
use App\Services\DataLoader\Normalizer;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Providers\ProductProvider
 */
class CityProviderTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::get
     */
    public function testGet(): void {
        // Prepare
        $countryA = Country::factory()->create();
        $countryB = Country::factory()->create();
        $factory  = static function (Normalizer $normalizer, City $city): City {
            return $city;
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
        $provider = $this->app->make(CityProvider::class);
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
            return City::factory()->create([
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
    }
}
