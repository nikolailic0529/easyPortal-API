<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Factories;

use App\Models\City;
use App\Models\Country;
use App\Models\Location as LocationModel;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\CityResolver;
use App\Services\DataLoader\Resolver\Resolvers\CountryResolver;
use App\Services\DataLoader\Resolver\Resolvers\LocationResolver;
use App\Services\DataLoader\Schema\Location;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Services\DataLoader\Testing\Helper;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;
use Tests\WithoutGlobalScopes;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factory\Factories\LocationFactory
 */
class LocationFactoryTest extends TestCase {
    use WithoutGlobalScopes;
    use WithQueryLog;
    use Helper;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::find
     */
    public function testFind(): void {
        $factory  = $this->app->make(LocationFactory::class);
        $location = new Location([
            'zip'         => $this->faker->postcode(),
            'address'     => $this->faker->streetAddress(),
            'city'        => $this->faker->city(),
            'country'     => $this->faker->country(),
            'countryCode' => $this->faker->countryCode(),
            'latitude'    => (string) $this->faker->latitude(),
            'longitude'   => (string) $this->faker->longitude(),
        ]);

        $this->flushQueryLog();

        $factory->find($location);

        self::assertCount(2, $this->getQueryLog());
    }

    /**
     * @covers ::create
     *
     * @dataProvider dataProviderCreate
     */
    public function testCreate(?string $expected, Type $type): void {
        $factory = Mockery::mock(LocationFactory::class);
        $factory->makePartial();
        $factory->shouldAllowMockingProtectedMethods();

        if ($expected) {
            $factory->shouldReceive($expected)
                ->once()
                ->with($type)
                ->andReturns();
        } else {
            self::expectException(InvalidArgumentException::class);
            self::expectErrorMessageMatches('/^The `\$type` must be instance of/');
        }

        $factory->create($type);
    }

    /**
     * @covers ::createFromLocation
     */
    public function testCreateFromLocation(): void {
        $latitude  = (string) $this->faker->latitude();
        $longitude = (string) $this->faker->longitude();
        $country   = Country::factory()->make();
        $city      = City::factory()->make([
            'country_id' => $country,
        ]);
        $location  = new Location([
            'zip'         => $this->faker->postcode(),
            'address'     => $this->faker->streetAddress(),
            'city'        => $this->faker->city(),
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

        $factory->create($location);
    }

    /**
     * @covers ::createFromLocation
     */
    public function testCreateFromLocationCityWithState(): void {
        $latitude  = (string) $this->faker->latitude();
        $longitude = (string) $this->faker->longitude();
        $country   = Country::factory()->make();
        $city      = City::factory()->make([
            'country_id' => $country,
        ]);
        $state     = $this->faker->state();
        $cityName  = $this->faker->city();
        $location  = new Location([
            'zip'         => $this->faker->postcode(),
            'address'     => $this->faker->streetAddress(),
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

        $factory->create($location);
    }

    /**
     * @covers ::createFromLocation
     */
    public function testCreateFromLocationWithoutZip(): void {
        $state    = $this->faker->state();
        $cityName = $this->faker->city();
        $location = new Location([
            'zip'     => null,
            'address' => $this->faker->streetAddress(),
            'city'    => "{$cityName},  {$state}",
        ]);

        $factory = $this->app->make(LocationFactory::class);

        self::assertNull($factory->create($location));
    }

    /**
     * @covers ::createFromAsset
     */
    public function testCreateFromAsset(): void {
        $latitude  = (string) $this->faker->latitude();
        $longitude = (string) $this->faker->longitude();
        $country   = Country::factory()->make();
        $city      = City::factory()->make([
            'country_id' => $country,
        ]);
        $assert    = new ViewAsset([
            'zip'         => $this->faker->postcode(),
            'address'     => $this->faker->streetAddress(),
            'city'        => $this->faker->city(),
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

        $factory->create($assert);
    }

    /**
     * @covers ::createFromAsset
     */
    public function testCreateFromAssetCityWithState(): void {
        $latitude  = (string) $this->faker->latitude();
        $longitude = (string) $this->faker->longitude();
        $country   = Country::factory()->make();
        $city      = City::factory()->make([
            'country_id' => $country,
        ]);
        $state     = $this->faker->state();
        $cityName  = $this->faker->city();
        $assert    = new ViewAsset([
            'zip'         => $this->faker->postcode(),
            'address'     => $this->faker->streetAddress(),
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

        $factory->create($assert);
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
        self::assertEquals($country, $factory->country($country->code, $country->name));
        self::assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // If not - it should be created
        $created = $factory->country(' CD ', ' Country  Name ');

        self::assertNotNull($created);
        self::assertTrue($created->wasRecentlyCreated);
        self::assertEquals('CD', $created->code);
        self::assertEquals('Country Name', $created->name);
        self::assertCount(2, $this->getQueryLog());
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
        self::assertEquals($city, $factory->city($country, $city->key));
        self::assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // If not - it should be created
        $created = $factory->city($country, ' City  Name ');

        self::assertNotNull($created);
        self::assertTrue($created->wasRecentlyCreated);
        self::assertEquals($country->getKey(), $created->country_id);
        self::assertEquals('City Name', $created->key);
        self::assertEquals('City Name', $created->name);
        self::assertCount(2, $this->getQueryLog());
    }

    /**
     * @covers ::location
     */
    public function testLocation(): void {
        // Prepare
        $normalizer = $this->app->make(Normalizer::class);
        $resolver   = $this->app->make(LocationResolver::class);
        $location   = LocationModel::factory()
            ->hasCountry(Country::factory())
            ->hasCity(City::factory())
            ->create();
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
        self::assertEquals($location, $factory->location(
            $country,
            $city,
            $location->postcode,
            $location->line_one,
            $location->line_two,
            $location->state,
            $location->latitude,
            $location->longitude,
        ));
        self::assertNotEquals('', $location->state);
        self::assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // If not - it should be created
        $state     = " {$this->faker->state()} ";
        $postcode  = " {$this->faker->postcode()} ";
        $lineOne   = " {$this->faker->streetAddress()} ";
        $lineTwo   = " {$this->faker->secondaryAddress()} ";
        $latitude  = " {$this->faker->latitude()} ";
        $longitude = " {$this->faker->longitude()} ";
        $created   = $factory->location(
            $country,
            $city,
            $postcode,
            $lineOne,
            $lineTwo,
            $state,
            $latitude,
            $longitude,
        );

        self::assertNotNull($created);
        self::assertEquals($country->getKey(), $created->country_id);
        self::assertEquals($city->getKey(), $created->city_id);
        self::assertEquals($normalizer->string($postcode), $created->postcode);
        self::assertEquals($normalizer->string($state), $created->state);
        self::assertEquals($normalizer->string($lineOne), $created->line_one);
        self::assertEquals($normalizer->string($lineTwo), $created->line_two);
        self::assertEquals($this->latitude($normalizer->coordinate($latitude)), $created->latitude);
        self::assertEquals($this->longitude($normalizer->coordinate($longitude)), $created->longitude);
        self::assertNotNull($created->geohash);
        self::assertCount(2, $this->getQueryLog());

        $this->flushQueryLog();

        // If state empty it should be updated
        $state          = "{$state} 2";
        $created->state = '';
        $updated        = $factory->location(
            $country,
            $city,
            $created->postcode,
            $created->line_one,
            $created->line_two,
            $state,
            $created->latitude,
            $created->longitude,
        );

        self::assertSame($created, $updated);
        self::assertEquals($normalizer->string($state), $updated->state);
        self::assertCount(1, $this->getQueryLog());
    }

    /**
     * @covers ::geohash
     *
     * @dataProvider dataProviderGeohash
     */
    public function testGeohash(?string $expected, ?string $latitude, ?string $longitude): void {
        $factory = new class() extends LocationFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            public function geohash(?string $latitude, ?string $longitude): ?string {
                return parent::geohash($latitude, $longitude);
            }
        };

        self::assertEquals($expected, $factory->geohash($latitude, $longitude));
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
    public function dataProviderGeohash(): array {
        return [
            'null'              => [null, null, null],
            'latitude is null'  => [null, null, '43.296482'],
            'longitude is null' => [null, '5.36978', null],
            'valid'             => ['spey61yhkcnp', '43.296482', '5.36978'],
            'invalid'           => [null, 'a', 'b'],
        ];
    }
    // </editor-fold>
}
