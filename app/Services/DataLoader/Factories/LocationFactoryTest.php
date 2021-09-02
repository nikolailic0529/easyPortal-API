<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\City;
use App\Models\Country;
use App\Models\Customer;
use App\Models\Location as LocationModel;
use App\Models\Model;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\CityResolver;
use App\Services\DataLoader\Resolvers\CountryResolver;
use App\Services\DataLoader\Resolvers\LocationResolver;
use App\Services\DataLoader\Schema\Location;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Services\DataLoader\Testing\Helper;
use Closure;
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
    use Helper;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::find
     */
    public function testFind(): void {
        $factory  = $this->app->make(LocationFactory::class);
        $customer = Customer::factory()->make();
        $location = new Location([
            'zip'         => $this->faker->postcode,
            'address'     => $this->faker->streetAddress,
            'city'        => $this->faker->city,
            'country'     => $this->faker->country,
            'countryCode' => $this->faker->countryCode,
            'latitude'    => (string) $this->faker->latitude,
            'longitude'   => (string) $this->faker->longitude,
        ]);

        $this->flushQueryLog();

        $factory->find($customer, $location);

        $this->assertCount(2, $this->getQueryLog());
    }

    /**
     * @covers ::create
     *
     * @dataProvider dataProviderCreate
     */
    public function testCreate(?string $expected, Type $type): void {
        $customer = new Customer();
        $factory  = Mockery::mock(LocationFactory::class);
        $factory->makePartial();
        $factory->shouldAllowMockingProtectedMethods();

        if ($expected) {
            $factory->shouldReceive($expected)
                ->once()
                ->with($customer, $type)
                ->andReturns();
        } else {
            $this->expectException(InvalidArgumentException::class);
            $this->expectErrorMessageMatches('/^The `\$type` must be instance of/');
        }

        $factory->create($customer, $type);
    }

    /**
     * @covers ::createFromLocation
     */
    public function testCreateFromLocation(): void {
        $latitude  = (string) $this->faker->latitude;
        $longitude = (string) $this->faker->longitude;
        $customer  = Customer::factory()->make();
        $country   = Country::factory()->make();
        $city      = City::factory()->make([
            'country_id' => $country,
        ]);
        $location  = new Location([
            'zip'         => $this->faker->postcode,
            'address'     => $this->faker->streetAddress,
            'city'        => $this->faker->city,
            'country'     => $country->name,
            'countryCode' => $country->code,
            'latitude'    => $latitude,
            'longitude'   => $longitude,
        ]);

        $factory = Mockery::mock(LocationFactory::class);
        $factory->makePartial();
        $factory->shouldAllowMockingProtectedMethods();
        $factory->shouldReceive('country')
            ->once()
            ->with($country->code, $country->name)
            ->andReturn($country);
        $factory
            ->shouldReceive('city')
            ->once()
            ->with($country, $location->city)
            ->andReturn($city);
        $factory
            ->shouldReceive('location')
            ->once()
            ->with(
                $customer,
                $country,
                $city,
                $location->zip,
                $location->address,
                '',
                '',
                $latitude,
                $longitude,
            )
            ->andReturns();

        $factory->create($customer, $location);
    }

    /**
     * @covers ::createFromLocation
     */
    public function testCreateFromLocationCityWithState(): void {
        $latitude  = (string) $this->faker->latitude;
        $longitude = (string) $this->faker->longitude;
        $customer  = Customer::factory()->make();
        $country   = Country::factory()->make();
        $city      = City::factory()->make([
            'country_id' => $country,
        ]);
        $state     = $this->faker->state;
        $cityName  = $this->faker->city;
        $location  = new Location([
            'zip'         => $this->faker->postcode,
            'address'     => $this->faker->streetAddress,
            'city'        => "{$cityName},  {$state}",
            'country'     => $country->name,
            'countryCode' => $country->code,
            'latitude'    => $latitude,
            'longitude'   => $longitude,
        ]);

        $factory = Mockery::mock(LocationFactory::class);
        $factory->makePartial();
        $factory->shouldAllowMockingProtectedMethods();
        $factory->shouldReceive('country')
            ->once()
            ->with($country->code, $country->name)
            ->andReturn($country);
        $factory
            ->shouldReceive('city')
            ->once()
            ->with($country, $cityName)
            ->andReturn($city);
        $factory
            ->shouldReceive('location')
            ->once()
            ->with(
                $customer,
                $country,
                $city,
                $location->zip,
                $location->address,
                '',
                "  {$state}",
                $latitude,
                $longitude,
            )
            ->andReturns();

        $factory->create($customer, $location);
    }

    /**
     * @covers ::createFromLocation
     */
    public function testCreateFromLocationWithoutZip(): void {
        $customer = Customer::factory()->make();
        $state    = $this->faker->state;
        $cityName = $this->faker->city;
        $location = new Location([
            'zip'     => null,
            'address' => $this->faker->streetAddress,
            'city'    => "{$cityName},  {$state}",
        ]);

        $factory = $this->app->make(LocationFactory::class);

        $this->assertNull($factory->create($customer, $location));
    }

    /**
     * @covers ::createFromAsset
     */
    public function testCreateFromAsset(): void {
        $latitude  = (string) $this->faker->latitude;
        $longitude = (string) $this->faker->longitude;
        $customer  = Customer::factory()->make();
        $country   = Country::factory()->make();
        $city      = City::factory()->make([
            'country_id' => $country,
        ]);
        $assert    = new ViewAsset([
            'zip'         => $this->faker->postcode,
            'address'     => $this->faker->streetAddress,
            'city'        => $this->faker->city,
            'country'     => $country->name,
            'countryCode' => $country->code,
            'latitude'    => $latitude,
            'longitude'   => $longitude,
        ]);

        $factory = Mockery::mock(LocationFactory::class);
        $factory->makePartial();
        $factory->shouldAllowMockingProtectedMethods();
        $factory->shouldReceive('country')
            ->once()
            ->with($country->code, $country->name)
            ->andReturn($country);
        $factory
            ->shouldReceive('city')
            ->once()
            ->with($country, $assert->city)
            ->andReturn($city);
        $factory
            ->shouldReceive('location')
            ->once()
            ->with(
                $customer,
                $country,
                $city,
                $assert->zip,
                $assert->address,
                '',
                '',
                $latitude,
                $longitude,
            )
            ->andReturns();

        $factory->create($customer, $assert);
    }

    /**
     * @covers ::createFromAsset
     */
    public function testCreateFromAssetCityWithState(): void {
        $latitude  = (string) $this->faker->latitude;
        $longitude = (string) $this->faker->longitude;
        $customer  = Customer::factory()->make();
        $country   = Country::factory()->make();
        $city      = City::factory()->make([
            'country_id' => $country,
        ]);
        $state     = $this->faker->state;
        $cityName  = $this->faker->city;
        $assert    = new ViewAsset([
            'zip'         => $this->faker->postcode,
            'address'     => $this->faker->streetAddress,
            'city'        => "{$cityName},  {$state}",
            'country'     => $country->name,
            'countryCode' => $country->code,
            'latitude'    => $latitude,
            'longitude'   => $longitude,
        ]);

        $factory = Mockery::mock(LocationFactory::class);
        $factory->makePartial();
        $factory->shouldAllowMockingProtectedMethods();
        $factory->shouldReceive('country')
            ->once()
            ->with($country->code, $country->name)
            ->andReturn($country);
        $factory
            ->shouldReceive('city')
            ->once()
            ->with($country, $cityName)
            ->andReturn($city);
        $factory
            ->shouldReceive('location')
            ->once()
            ->with(
                $customer,
                $country,
                $city,
                $assert->zip,
                $assert->address,
                '',
                "  {$state}",
                $latitude,
                $longitude,
            )
            ->andReturns();

        $factory->create($customer, $assert);
    }

    /**
     * @covers ::country
     */
    public function testCountry(): void {
        // Prepare
        $normalizer = $this->app->make(Normalizer::class);
        $resolver   = $this->app->make(CountryResolver::class);
        $country    = Country::factory()->create();

        $factory = new class($normalizer, $resolver) extends LocationFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected CountryResolver $countryResolver,
            ) {
                // empty
            }

            public function country(string $code, string $name): Country {
                return parent::country($code, $name);
            }
        };

        $this->flushQueryLog();

        // If model exists - no action required
        $this->assertEquals($country, $factory->country($country->code, $country->name));
        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // If not - it should be created
        $created = $factory->country(' CD ', ' Country  Name ');

        $this->assertNotNull($created);
        $this->assertTrue($created->wasRecentlyCreated);
        $this->assertEquals('CD', $created->code);
        $this->assertEquals('Country Name', $created->name);
        $this->assertCount(2, $this->getQueryLog());
    }

    /**
     * @covers ::city
     */
    public function testCity(): void {
        // Prepare
        $normalizer = $this->app->make(Normalizer::class);
        $country    = Country::factory()->create();
        $city       = City::factory()->create([
            'country_id' => $country,
        ]);
        $resolver   = $this->app->make(CityResolver::class);

        $factory = new class($normalizer, $resolver) extends LocationFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected CityResolver $cityResolver,
            ) {
                // empty
            }

            public function city(Country $country, string $name): City {
                return parent::city($country, $name);
            }
        };

        $this->flushQueryLog();

        // If model exists - no action required
        $this->assertEquals($city, $factory->city($country, $city->name));
        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // If not - it should be created
        $created = $factory->city($country, ' City  Name ');

        $this->assertNotNull($created);
        $this->assertTrue($created->wasRecentlyCreated);
        $this->assertEquals($country->getKey(), $created->country_id);
        $this->assertEquals('City Name', $created->name);
        $this->assertCount(2, $this->getQueryLog());
    }

    /**
     * @covers ::location
     *
     * @dataProvider dataProviderLocation
     */
    public function testLocation(Closure $factory): void {
        // Prepare
        $normalizer = $this->app->make(Normalizer::class);
        $resolver   = $this->app->make(LocationResolver::class);
        $customer   = $factory($this);
        $location   = LocationModel::factory()
            ->hasCountry(Country::factory())
            ->hasCity(City::factory())
            ->create([
                'object_type' => $customer->getMorphClass(),
                'object_id'   => $customer->getKey(),
            ]);
        $country    = $location->country;
        $city       = $location->city;

        $factory = new class($normalizer, $resolver) extends LocationFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected LocationResolver $locationResolver,
            ) {
                // empty
            }

            public function location(
                Model $object,
                Country $country,
                City $city,
                string $postcode,
                string $lineOne,
                string $lineTwo,
                string $state,
                ?string $latitude,
                ?string $longitude,
            ): LocationModel {
                return parent::location(
                    $object,
                    $country,
                    $city,
                    $postcode,
                    $lineOne,
                    $lineTwo,
                    $state,
                    $latitude,
                    $longitude,
                );
            }
        };

        $this->flushQueryLog();

        // If model exists - no action required
        if ($customer->exists) {
            $this->assertEquals($location, $factory->location(
                $customer,
                $country,
                $city,
                $location->postcode,
                $location->line_one,
                $location->line_two,
                $location->state,
                $location->latitude,
                $location->longitude,
            ));
            $this->assertNotEquals('', $location->state);
            $this->assertCount(1, $this->getQueryLog());
        } else {
            $this->assertNotNull($factory->location(
                $customer,
                $country,
                $city,
                $location->postcode,
                $location->line_one,
                $location->line_two,
                $location->state,
                $location->latitude,
                $location->longitude,
            ));
            $this->assertNotEquals('', $location->state);
            $this->assertCount(0, $this->getQueryLog());
        }

        $this->flushQueryLog();

        // If not - it should be created
        $state     = " {$this->faker->state} ";
        $postcode  = " {$this->faker->postcode} ";
        $lineOne   = " {$this->faker->streetAddress} ";
        $lineTwo   = " {$this->faker->secondaryAddress} ";
        $latitude  = " {$this->faker->latitude} ";
        $longitude = " {$this->faker->longitude} ";
        $created   = $factory->location(
            $customer,
            $country,
            $city,
            $postcode,
            $lineOne,
            $lineTwo,
            $state,
            $latitude,
            $longitude,
        );

        $this->assertNotNull($created);
        $this->assertEquals($customer->exists, $created->exists);
        $this->assertEquals($customer->getMorphClass(), $created->object_type);
        $this->assertEquals($customer->getKey(), $created->object_id);
        $this->assertEquals($country->getKey(), $created->country_id);
        $this->assertEquals($city->getKey(), $created->city_id);
        $this->assertEquals($normalizer->string($postcode), $created->postcode);
        $this->assertEquals($normalizer->string($state), $created->state);
        $this->assertEquals($normalizer->string($lineOne), $created->line_one);
        $this->assertEquals($normalizer->string($lineTwo), $created->line_two);
        $this->assertEquals($this->latitude($normalizer->coordinate($latitude)), $created->latitude);
        $this->assertEquals($this->longitude($normalizer->coordinate($longitude)), $created->longitude);
        $this->assertCount($customer->exists ? 2 : 0, $this->getQueryLog());

        $this->flushQueryLog();

        // If state empty it should be updated
        $state          = "{$state} 2";
        $created->state = '';
        $updated        = $factory->location(
            $customer,
            $country,
            $city,
            $created->postcode,
            $created->line_one,
            $created->line_two,
            $state,
            $created->latitude,
            $created->longitude,
        );

        $this->assertSame($created, $updated);
        $this->assertEquals($normalizer->string($state), $updated->state);
        $this->assertCount((int) $customer->exists, $this->getQueryLog());
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderCreate(): array {
        return [
            Location::class  => ['createFromLocation', new Location()],
            ViewAsset::class => ['createFromAsset', new ViewAsset()],
            'Unknown'        => [
                null,
                new class() extends Type {
                    // empty
                },
            ],
        ];
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderLocation(): array {
        return [
            'Exists'     => [
                static function (): Customer {
                    return Customer::factory()->create();
                },
            ],
            'Not Exists' => [
                static function (): Customer {
                    return Customer::factory()->make();
                },
            ],
        ];
    }
    // </editor-fold>
}
