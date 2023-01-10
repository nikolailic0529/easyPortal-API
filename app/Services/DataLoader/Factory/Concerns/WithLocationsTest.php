<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Data\City;
use App\Models\Data\Country;
use App\Models\Data\Location as LocationModel;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Resolver\Resolvers\CityResolver;
use App\Services\DataLoader\Resolver\Resolvers\CountryResolver;
use App\Services\DataLoader\Resolver\Resolvers\LocationResolver;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\Types\Location;
use App\Services\DataLoader\Testing\Helper;
use App\Utils\Eloquent\Model;
use Closure;
use Exception;
use Tests\TestCase;
use Tests\WithoutGlobalScopes;
use Tests\WithQueryLogs;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factory\Concerns\WithLocations
 */
class WithLocationsTest extends TestCase {
    use WithoutGlobalScopes;
    use WithQueryLogs;
    use Helper;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::location
     */
    public function testLocation(): void {
        // Prepare
        $country = Country::factory()->create();
        $city    = City::factory()->create([
            'country_id' => $country,
        ]);
        $model   = LocationModel::factory()->create([
            'country_id' => $country,
            'city_id'    => $city,
            'line_two'   => '',
        ]);
        $factory = new class(
            $this->app->make(LocationResolver::class),
            $this->app->make(CountryResolver::class),
            $this->app->make(CityResolver::class),
        ) extends Factory {
            use WithLocations {
                location as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected LocationResolver $locationResolver,
                protected CountryResolver $countryResolver,
                protected CityResolver $cityResolver,
            ) {
                // empty
            }

            protected function getLocationResolver(): LocationResolver {
                return $this->locationResolver;
            }

            protected function getCountryResolver(): CountryResolver {
                return $this->countryResolver;
            }

            protected function getCityResolver(): CityResolver {
                return $this->cityResolver;
            }

            public function getModel(): string {
                return Model::class;
            }

            public function create(Type $type, bool $force = false): ?Model {
                return null;
            }
        };

        // If model exists - no action required
        $model    = $model->loadMissing(['country', 'city']);
        $queries  = $this->getQueryLog()->flush();
        $location = new Location([
            'zip'          => $model->postcode,
            'address'      => "{$model->line_one} {$model->line_two}",
            'city'         => "{$city->key}, {$model->state}",
            'locationType' => null,
            'latitude'     => $model->latitude,
            'longitude'    => $model->longitude,
            'country'      => $country->name,
            'countryCode'  => $country->code,
        ]);

        self::assertEquals($model, $factory->location($location));
        self::assertCount(3, $queries);

        // If not - it should be created
        $state     = $this->faker->state();
        $postcode  = $this->faker->postcode();
        $lineOne   = $this->faker->streetAddress();
        $lineTwo   = $this->faker->secondaryAddress();
        $latitude  = (string) $this->faker->latitude();
        $longitude = (string) $this->faker->longitude();
        $queries   = $this->getQueryLog()->flush();
        $location  = new Location([
            'zip'          => $postcode,
            'address'      => "{$lineOne} {$lineTwo}",
            'city'         => "{$city->key}, {$state}",
            'locationType' => null,
            'latitude'     => $latitude,
            'longitude'    => $longitude,
            'country'      => $country->name,
            'countryCode'  => $country->code,
        ]);
        $created   = $factory->location($location);

        self::assertNotNull($created);
        self::assertEquals($country->getKey(), $created->country_id);
        self::assertEquals($city->getKey(), $created->city_id);
        self::assertEquals($postcode, $created->postcode);
        self::assertEquals($state, $created->state);
        self::assertEquals("{$lineOne} {$lineTwo}", $created->line_one);
        self::assertEquals('', $created->line_two);
        self::assertEquals($this->latitude($latitude), $created->latitude);
        self::assertEquals($this->longitude($longitude), $created->longitude);
        self::assertNotNull($created->geohash);
        self::assertCount(2, $queries);

        // If state empty it should be updated
        $state          = "{$state} 2";
        $location->city = "{$city->key}, {$state}";
        $created->state = '';
        $queries        = $this->getQueryLog()->flush();
        $updated        = $factory->location($location, false);

        self::assertSame($created, $updated);
        self::assertEquals('', $updated->state);

        $updated = $factory->location($location);

        self::assertSame($created, $updated);
        self::assertEquals($state, $updated->state);
        self::assertCount(1, $queries);

        // If empty the `null` should be returned
        $queries = $this->getQueryLog()->flush();

        self::assertNull($factory->location(new Location([
            'zip'          => '', // empty
            'address'      => "{$model->line_one} {$model->line_two}",
            'city'         => "{$city->key}, {$model->state}",
            'locationType' => null,
            'latitude'     => $model->latitude,
            'longitude'    => $model->longitude,
            'country'      => $country->name,
            'countryCode'  => $country->code,
        ])));
        self::assertNull($factory->location(new Location([
            'zip'          => $model->postcode,
            'address'      => "{$model->line_one} {$model->line_two}",
            'city'         => '', // empty
            'locationType' => null,
            'latitude'     => $model->latitude,
            'longitude'    => $model->longitude,
            'country'      => $country->name,
            'countryCode'  => $country->code,
        ])));

        self::assertCount(0, $queries);
    }

    /**
     * @covers ::country
     */
    public function testCountry(): void {
        // Prepare
        $resolver = $this->app->make(CountryResolver::class);
        $country  = Country::factory()->create();
        $factory  = new class($resolver) extends Factory {
            use WithLocations {
                country as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected CountryResolver $countryResolver,
            ) {
                // empty
            }

            protected function getLocationResolver(): LocationResolver {
                throw new Exception('should not be called');
            }

            protected function getCountryResolver(): CountryResolver {
                return $this->countryResolver;
            }

            protected function getCityResolver(): CityResolver {
                throw new Exception('should not be called');
            }

            public function getModel(): string {
                return Model::class;
            }

            public function create(Type $type, bool $force = false): ?Model {
                return null;
            }
        };

        // If model exists - no action required
        $queries = $this->getQueryLog()->flush();

        self::assertEquals($country, $factory->country($country->code, $country->name));
        self::assertCount(1, $queries);

        // If not - it should be created
        $queries = $this->getQueryLog()->flush();
        $created = $factory->country('??', 'Country Name');

        self::assertTrue($created->wasRecentlyCreated);
        self::assertEquals('??', $created->code);
        self::assertEquals('Country Name', $created->name);
        self::assertCount(2, $queries);

        // No name -> code should be used
        $created = $factory->country('AB', null);

        self::assertEquals('AB', $created->code);
        self::assertEquals('AB', $created->name);

        // No name -> name should be updated
        $queries = $this->getQueryLog()->flush();
        $created = $factory->country('AB', 'Name');

        self::assertEquals('AB', $created->code);
        self::assertEquals('Name', $created->name);
        self::assertCount(1, $queries);

        // Unknown Country -> name should be updated
        $queries = $this->getQueryLog()->flush();
        $updated = $factory->country($factory->country('UN', 'Unknown Country')->code, 'Name');

        self::assertEquals('Name', $updated->name);
        self::assertCount(3, $queries);

        // Name -> should not be updated
        $queries = $this->getQueryLog()->flush();
        $updated = $factory->country($factory->country('XX', 'Name')->code, 'New Name');

        self::assertEquals('Name', $updated->name);
        self::assertCount(2, $queries);
    }

    /**
     * @covers ::city
     */
    public function testCity(): void {
        // Prepare
        $country  = Country::factory()->create();
        $city     = City::factory()->create([
            'country_id' => $country,
        ]);
        $resolver = $this->app->make(CityResolver::class);
        $factory  = new class($resolver) extends Factory {
            use WithLocations {
                city as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected CityResolver $cityResolver,
            ) {
                // empty
            }

            protected function getLocationResolver(): LocationResolver {
                throw new Exception('should not be called');
            }

            protected function getCountryResolver(): CountryResolver {
                throw new Exception('should not be called');
            }

            protected function getCityResolver(): CityResolver {
                return $this->cityResolver;
            }

            public function getModel(): string {
                return Model::class;
            }

            public function create(Type $type, bool $force = false): ?Model {
                return null;
            }
        };

        // If model exists - no action required
        $queries = $this->getQueryLog()->flush();

        self::assertEquals($city, $factory->city($country, $city->key));
        self::assertCount(1, $queries);

        // If not - it should be created
        $queries = $this->getQueryLog()->flush();
        $created = $factory->city($country, 'City Name');

        self::assertTrue($created->wasRecentlyCreated);
        self::assertEquals($country->getKey(), $created->country_id);
        self::assertEquals('City Name', $created->key);
        self::assertEquals('City Name', $created->name);
        self::assertCount(2, $queries);
    }

    /**
     * @covers ::geohash
     *
     * @dataProvider dataProviderGeohash
     */
    public function testGeohash(?string $expected, ?string $latitude, ?string $longitude): void {
        $factory = new class() extends Factory {
            use WithLocations {
                geohash as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            protected function getLocationResolver(): LocationResolver {
                throw new Exception('should not be called');
            }

            protected function getCountryResolver(): CountryResolver {
                throw new Exception('should not be called');
            }

            protected function getCityResolver(): CityResolver {
                throw new Exception('should not be called');
            }

            public function getModel(): string {
                return Model::class;
            }

            public function create(Type $type, bool $force = false): ?Model {
                return null;
            }
        };

        self::assertEquals($expected, $factory->geohash($latitude, $longitude));
    }

    /**
     * @covers ::isLocationEmpty
     *
     * @dataProvider dataProviderIsLocationEmpty
     *
     * @param Closure(static): Location $locationFactory
     */
    public function testIsLocationEmpty(bool $expected, Closure $locationFactory): void {
        $location = $locationFactory($this);
        $factory  = new class() extends Factory {
            use WithLocations {
                isLocationEmpty as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            protected function getLocationResolver(): LocationResolver {
                throw new Exception('should not be called');
            }

            protected function getCountryResolver(): CountryResolver {
                throw new Exception('should not be called');
            }

            protected function getCityResolver(): CityResolver {
                throw new Exception('should not be called');
            }

            public function getModel(): string {
                return Model::class;
            }

            public function create(Type $type, bool $force = false): ?Model {
                return null;
            }
        };

        self::assertEquals($expected, $factory->isLocationEmpty($location));
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
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

    /**
     * @return array<mixed>
     */
    public function dataProviderIsLocationEmpty(): array {
        return [
            'ok'      => [
                false,
                static function (TestCase $test): Location {
                    return new Location([
                        'zip'  => $test->faker->postcode(),
                        'city' => $test->faker->city(),
                    ]);
                },
            ],
            'no zip'  => [
                true,
                static function (TestCase $test): Location {
                    return new Location([
                        'city' => $test->faker->city(),
                    ]);
                },
            ],
            'no city' => [
                true,
                static function (TestCase $test): Location {
                    return new Location([
                        'zip' => $test->faker->postcode(),
                    ]);
                },
            ],
        ];
    }
    // </editor-fold>
}
