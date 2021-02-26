<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\City;
use App\Models\Country;
use App\Models\Location as LocationModel;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Providers\CityProvider;
use App\Services\DataLoader\Providers\CountryProvider;
use App\Services\DataLoader\Providers\LocationProvider;
use App\Services\DataLoader\Schema\Location;
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
        $factory    = new class() extends LocationFactory {
            public function __construct() {
                // parent not needed
            }

            public function country(?Country $country, Normalizer $normalizer, string $code, string $name): Country {
                return parent::country($country, $normalizer, $code, $name);
            }
        };

        $this->flushQueryLog();

        // If model exists - no action required
        $this->assertSame($country, $factory->country($country, $normalizer, '', ''));
        $this->assertCount(0, $this->getQueryLog());

        // If not - it should be created
        $created = $factory->country(null, $normalizer, ' CD ', ' Country  Name ');

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
        $factory    = new class() extends LocationFactory {
            public function __construct() {
                // parent not needed
            }

            public function city(?City $city, Normalizer $normalizer, Country $country, string $name): City {
                return parent::city($city, $normalizer, $country, $name);
            }
        };

        $this->flushQueryLog();

        // If model exists - no action required
        $this->assertSame($city, $factory->city($city, $normalizer, $country, ''));
        $this->assertCount(0, $this->getQueryLog());

        // If not - it should be created
        $created = $factory->city(null, $normalizer, $country, ' City  Name ');

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
        $factory    = new class() extends LocationFactory {
            public function __construct() {
                // parent not needed
            }

            public function location(
                ?LocationModel $location,
                Normalizer $normalizer,
                Country $country,
                City $city,
                string $postcode,
                string $lineOne,
                string $lineTwo,
                string $state,
            ): LocationModel {
                return parent::location(
                    $location,
                    $normalizer,
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
        $this->assertSame($location, $factory->location($location, $normalizer, $country, $city, '', '', '', ''));
        $this->assertNotEquals('', $location->state);
        $this->assertCount(0, $this->getQueryLog());

        // If not - it should be created
        $state    = " {$this->faker->state} ";
        $postcode = " {$this->faker->postcode} ";
        $lineOne  = " {$this->faker->streetAddress} ";
        $lineTwo  = " {$this->faker->secondaryAddress} ";
        $created  = $factory->location(null, $normalizer, $country, $city, $postcode, $lineOne, $lineTwo, $state);

        $this->assertNotNull($created);
        $this->assertTrue($created->wasRecentlyCreated);
        $this->assertEquals($country->getKey(), $created->country_id);
        $this->assertEquals($city->getKey(), $created->city_id);
        $this->assertEquals($normalizer->string($postcode), $created->postcode);
        $this->assertEquals($normalizer->string($state), $created->state);
        $this->assertEquals($normalizer->string($lineOne), $created->line_one);
        $this->assertEquals($normalizer->string($lineTwo), $created->line_two);
        $this->assertCount(1, $this->getQueryLog());

        // If state empty it should be updated
        $state           = $this->faker->state;
        $location->state = '';

        $factory->location($location, $normalizer, $country, $city, '', '', '', $state);

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
