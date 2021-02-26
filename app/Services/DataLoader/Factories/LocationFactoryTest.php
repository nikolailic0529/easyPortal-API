<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\City;
use App\Models\Country;
use App\Models\Location as LocationModel;
use App\Services\DataLoader\Cache\Cache;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Providers\CityProvider;
use App\Services\DataLoader\Providers\CountryProvider;
use App\Services\DataLoader\Providers\LocationProvider;
use App\Services\DataLoader\Schema\Type;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factories\LocationFactory
 */
class LocationFactoryTest extends TestCase {
    use WithQueryLog;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::create
     */
    public function testCreateUnknownType(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectErrorMessageMatches('/^The `\$type` must be instance of/');

        $this->app->make(LocationFactory::class)->create(new class() extends Type {
            // empty
        });
    }

    /**
     * @covers ::country
     */
    public function testCountry(): void {
        // Prepare
        $normalizer = $this->app->make(Normalizer::class);
        $country    = Country::factory()->make();
        $cache      = Mockery::mock(Cache::class);
        $provider   = Mockery::mock(CountryProvider::class, [$normalizer]);

        $cache->shouldReceive('has')->times(2)->andReturn(true, false);
        $cache->shouldReceive('get')->times(1)->andReturn($country);
        $cache->shouldReceive('put')->times(1)->andReturns();

        $provider->makePartial();
        $provider->shouldAllowMockingProtectedMethods();
        $provider->shouldReceive('getCache')->times(4)->andReturn($cache);

        $factory = new class($normalizer, $provider) extends LocationFactory {
            public function __construct(Normalizer $normalizer, CountryProvider $provider) {
                $this->normalizer = $normalizer;
                $this->countries  = $provider;
            }

            public function country(string $code, string $name): Country {
                return parent::country($code, $name);
            }
        };

        $this->flushQueryLog();

        // If model exists - no action required
        $this->assertSame($country, $factory->country('', ''));
        $this->assertCount(0, $this->getQueryLog());

        // If not - it should be created
        $created = $factory->country(' CD ', ' Country  Name ');

        $this->assertNotNull($created);
        $this->assertTrue($created->wasRecentlyCreated);
        $this->assertEquals('CD', $created->code);
        $this->assertEquals('Country Name', $created->name);
        $this->assertCount(1, $this->getQueryLog());
    }

    /**
     * @covers ::city
     */
    public function testCity(): void {
        // Prepare
        $normalizer = $this->app->make(Normalizer::class);
        $country    = Country::factory()->create();
        $city       = City::factory()->make();
        $cache      = Mockery::mock(Cache::class);
        $provider   = Mockery::mock(CityProvider::class, [$normalizer]);

        $cache->shouldReceive('has')->times(2)->andReturn(true, false);
        $cache->shouldReceive('get')->times(1)->andReturn($city);
        $cache->shouldReceive('put')->times(1)->andReturns();

        $provider->makePartial();
        $provider->shouldAllowMockingProtectedMethods();
        $provider->shouldReceive('getCache')->times(4)->andReturn($cache);

        $factory = new class($normalizer, $provider) extends LocationFactory {
            public function __construct(Normalizer $normalizer, CityProvider $provider) {
                $this->normalizer = $normalizer;
                $this->cities     = $provider;
            }

            public function city(Country $country, string $name): City {
                return parent::city($country, $name);
            }
        };

        $this->flushQueryLog();

        // If model exists - no action required
        $this->assertSame($city, $factory->city($country, ''));
        $this->assertCount(0, $this->getQueryLog());

        // If not - it should be created
        $created = $factory->city($country, ' City  Name ');

        $this->assertNotNull($created);
        $this->assertTrue($created->wasRecentlyCreated);
        $this->assertEquals($country->getKey(), $created->country_id);
        $this->assertEquals('City Name', $created->name);
        $this->assertCount(1, $this->getQueryLog());
    }

    /**
     * @covers ::location
     */
    public function testLocation(): void {
        // Prepare
        $normalizer = $this->app->make(Normalizer::class);
        $country    = Country::factory()->create();
        $city       = City::factory()->create();
        $location   = LocationModel::factory()->make();
        $cache      = Mockery::mock(Cache::class);
        $provider   = Mockery::mock(LocationProvider::class, [$normalizer]);

        $cache->shouldReceive('has')->times(3)->andReturn(true, false, true);
        $cache->shouldReceive('get')->times(2)->andReturn($location);
        $cache->shouldReceive('put')->times(1)->andReturns();

        $provider->makePartial();
        $provider->shouldAllowMockingProtectedMethods();
        $provider->shouldReceive('getCache')->times(6)->andReturn($cache);

        $factory = new class($normalizer, $provider) extends LocationFactory {
            public function __construct(Normalizer $normalizer, LocationProvider $provider) {
                $this->normalizer = $normalizer;
                $this->locations  = $provider;
            }

            public function location(
                Country $country,
                City $city,
                string $postcode,
                string $lineOne,
                string $lineTwo,
                string $state,
            ): LocationModel {
                return parent::location(
                    $country,
                    $city,
                    $postcode,
                    $lineOne,
                    $lineTwo,
                    $state,
                );
            }
        };

        $this->flushQueryLog();

        // If model exists - no action required
        $this->assertSame($location, $factory->location($country, $city, '', '', '', ''));
        $this->assertNotEquals('', $location->state);
        $this->assertCount(0, $this->getQueryLog());

        // If not - it should be created
        $state    = " {$this->faker->state} ";
        $postcode = " {$this->faker->postcode} ";
        $lineOne  = " {$this->faker->streetAddress} ";
        $lineTwo  = " {$this->faker->secondaryAddress} ";
        $created  = $factory->location($country, $city, $postcode, $lineOne, $lineTwo, $state);

        $this->assertNotNull($created);
        $this->assertTrue($created->wasRecentlyCreated);
        $this->assertEquals($country->getKey(), $created->country_id);
        $this->assertEquals($city->getKey(), $created->city_id);
        $this->assertEquals($normalizer->string($postcode), $created->postcode);
        $this->assertEquals($normalizer->string($state), $created->state);
        $this->assertEquals($normalizer->string($lineOne), $created->line_one);
        $this->assertEquals($normalizer->string($lineTwo), $created->line_two);
        $this->assertCount(2, $this->getQueryLog());

        // If state empty it should be updated
        $state           = $this->faker->state;
        $location->state = '';

        $factory->location($country, $city, $location->postcode, $location->line_one, $location->line_two, $state);

        $this->assertEquals($state, $location->state);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderCreate(): array {
        return [
//            'location not exists + no state in city name' => [false, false],
'location exists + no state in city name' => [true, false],
//            'location not exists + state in city name'    => [false, true],
//            'location exists + state in city name'        => [true, true],
        ];
    }
    // </editor-fold>
}
