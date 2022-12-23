<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Factories;

use App\Models\Data\City;
use App\Models\Data\Country;
use App\Models\Data\Location as LocationModel;
use App\Services\DataLoader\Factory\ModelFactory;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\CityResolver;
use App\Services\DataLoader\Resolver\Resolvers\CountryResolver;
use App\Services\DataLoader\Resolver\Resolvers\LocationResolver;
use App\Services\DataLoader\Schema\Location;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewAsset;
use Illuminate\Contracts\Debug\ExceptionHandler;
use InvalidArgumentException;
use League\Geotools\Coordinate\Coordinate;
use League\Geotools\Geohash\Geohash;
use Throwable;

use function array_filter;
use function explode;
use function implode;
use function mb_strtoupper;
use function sprintf;
use function str_contains;

/**
 * @extends ModelFactory<LocationModel>
 */
class LocationFactory extends ModelFactory {
    private const UNKNOWN_COUNTRY_CODE = '??';
    private const UNKNOWN_COUNTRY_NAME = 'Unknown Country';

    public function __construct(
        ExceptionHandler $exceptionHandler,
        Normalizer $normalizer,
        protected CountryResolver $countryResolver,
        protected CityResolver $cityResolver,
        protected LocationResolver $locationResolver,
    ) {
        parent::__construct($exceptionHandler, $normalizer);
    }

    // <editor-fold desc="Factory">
    // =========================================================================
    public function getModel(): string {
        return LocationModel::class;
    }

    public function create(Type $type, bool $update = true): ?LocationModel {
        $model = null;

        if ($type instanceof Location) {
            $model = $this->createFromLocation($type, $update);
        } elseif ($type instanceof ViewAsset) {
            $model = $this->createFromAsset($type, $update);
        } else {
            throw new InvalidArgumentException(sprintf(
                'The `$type` must be instance of `%s`.',
                Location::class,
            ));
        }

        return $model;
    }
    // </editor-fold>

    // <editor-fold desc="Functions">
    // =========================================================================
    public function isEmpty(Location|ViewAsset $location): bool {
        return !($location->zip ?? null) || !($location->city ?? null);
    }

    protected function createFromLocation(Location $location, bool $update = true): ?LocationModel {
        // Is empty?
        if ($this->isEmpty($location)) {
            return null;
        }

        // Country may be unknown so we use Unknown
        $country = isset($location->countryCode)
            ? $this->country($location->countryCode, $location->country)
            : $this->country(self::UNKNOWN_COUNTRY_CODE, self::UNKNOWN_COUNTRY_NAME);

        // City? (it may contain State)
        $city  = $location->city;
        $state = '';

        if ($city && str_contains($city, ',')) {
            [$city, $state] = explode(',', $city, 2);
        }

        if (!$city) {
            return null;
        }

        // Location
        $object = $this->location(
            $country,
            $this->city($country, $city),
            (string) $location->zip,
            (string) $location->address,
            '',
            $state,
            $location->latitude,
            $location->longitude,
            $update,
        );

        // Return
        return $object;
    }

    protected function createFromAsset(ViewAsset $asset, bool $update = true): ?LocationModel {
        $location              = new Location();
        $location->zip         = $asset->zip ?? null;
        $location->city        = $asset->city ?? null;
        $location->address     = implode(' ', array_filter([$asset->address ?? null, $asset->address2 ?? null]));
        $location->country     = $asset->country ?? null;
        $location->countryCode = $asset->countryCode ?? null;
        $location->latitude    = $asset->latitude ?? null;
        $location->longitude   = $asset->longitude ?? null;

        return $this->createFromLocation($location, $update);
    }

    protected function country(string $code, ?string $name): Country {
        // Get/Create
        $code    = $this->getNormalizer()->string($code);
        $created = false;
        $factory = function (Country $country) use (&$created, $code, $name): Country {
            $created = !$country->exists;
            $code    = mb_strtoupper($code);

            if ($created || $country->name === self::UNKNOWN_COUNTRY_NAME || $country->name === $code) {
                $normalizer    = $this->getNormalizer();
                $country->code = $code;
                $country->name = $normalizer->string($name) ?: $code;

                $country->save();
            }

            return $country;
        };
        $country = $this->countryResolver->get(
            $code,
            static function () use ($factory): Country {
                return $factory(new Country());
            },
        );

        // Update
        if (!$created) {
            $factory($country);
        }

        // Return
        return $country;
    }

    protected function city(Country $country, string $name): City {
        $name = $this->getNormalizer()->string($name);
        $city = $this->cityResolver->get(
            $country,
            $name,
            static function () use ($country, $name): City {
                $city          = new City();
                $city->key     = $name;
                $city->name    = $name;
                $city->country = $country;

                $city->save();

                return $city;
            },
        );

        return $city;
    }

    protected function location(
        Country $country,
        City $city,
        string $postcode,
        string $lineOne,
        string $lineTwo,
        string $state,
        ?string $latitude,
        ?string $longitude,
        bool $update = true,
    ): LocationModel {
        $normalizer = $this->getNormalizer();
        $postcode   = $normalizer->string($postcode);
        $lineOne    = $normalizer->string($lineOne);
        $lineTwo    = $normalizer->string($lineTwo);
        $state      = $normalizer->string($state);
        $latitude   = $normalizer->coordinate($latitude);
        $longitude  = $normalizer->coordinate($longitude);
        $created    = false;
        $factory    = function (
            LocationModel $location,
        ) use (
            &$created,
            $country,
            $city,
            $postcode,
            $lineOne,
            $lineTwo,
            $state,
            $latitude,
            $longitude,
        ): LocationModel {
            $created             = !$location->exists;
            $location->country   = $country;
            $location->city      = $city;
            $location->postcode  = $postcode;
            $location->state     = $state;
            $location->line_one  = $lineOne;
            $location->line_two  = $lineTwo;
            $location->latitude  = $latitude;
            $location->longitude = $longitude;
            $location->geohash   = $this->geohash($latitude, $longitude);

            $location->save();

            return $location;
        };
        $location   = $this->locationResolver->get(
            $country,
            $city,
            $postcode,
            $lineOne,
            $lineTwo,
            static function () use ($factory): LocationModel {
                return $factory(new LocationModel());
            },
        );

        if (!$created && $update) {
            $factory($location);
        }

        return $location;
    }

    protected function geohash(?string $latitude, ?string $longitude): ?string {
        // Possible?
        if (!$latitude || !$longitude) {
            return null;
        }

        // Encode
        $geohash = null;

        try {
            $length     = LocationModel::GEOHASH_LENGTH;
            $coordinate = new Coordinate([$latitude, $longitude]);
            $geohash    = (new Geohash())->encode($coordinate, $length)->getGeohash();
        } catch (Throwable) {
            // empty
        }

        return $geohash;
    }
    // </editor-fold>
}
