<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\City;
use App\Models\Country;
use App\Models\Location as LocationModel;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\CityResolver;
use App\Services\DataLoader\Resolvers\CountryResolver;
use App\Services\DataLoader\Resolvers\LocationResolver;
use App\Services\DataLoader\Schema\Location;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewAsset;
use Illuminate\Contracts\Debug\ExceptionHandler;
use InvalidArgumentException;
use League\Geotools\Coordinate\Coordinate;
use League\Geotools\Geotools;
use Throwable;

use function array_filter;
use function end;
use function explode;
use function implode;
use function mb_strtoupper;
use function reset;
use function sprintf;
use function str_contains;

class LocationFactory extends ModelFactory {
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
    public function find(Type $type): ?LocationModel {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return parent::find($type);
    }

    public function create(Type $type): ?LocationModel {
        $model = null;

        if ($type instanceof Location) {
            $model = $this->createFromLocation($type);
        } elseif ($type instanceof ViewAsset) {
            $model = $this->createFromAsset($type);
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

    protected function createFromLocation(Location $location): ?LocationModel {
        // Is empty?
        if ($this->isEmpty($location)) {
            return null;
        }

        // Country is not yet available so we use Unknown
        $country = $location->countryCode
            ? $this->country($location->countryCode, $location->country ?: 'Unknown Country')
            : $this->country('??', 'Unknown Country');

        // City may contains State
        $city  = null;
        $state = '';

        if (str_contains($location->city, ',')) {
            $parts = explode(',', $location->city, 2);
            $state = end($parts);
            $city  = reset($parts);
        } else {
            $city = $location->city;
        }

        // City
        $city = $this->city($country, $city);

        // Location
        $object = $this->location(
            $country,
            $city,
            $location->zip,
            (string) $location->address,
            '',
            $state,
            $location->latitude,
            $location->longitude,
        );

        // Return
        return $object;
    }

    protected function createFromAsset(ViewAsset $asset): ?LocationModel {
        $location              = new Location();
        $location->zip         = $asset->zip ?? null;
        $location->city        = $asset->city ?? null;
        $location->address     = implode(' ', array_filter([$asset->address ?? null, $asset->address2 ?? null]));
        $location->country     = $asset->country ?? null;
        $location->countryCode = $asset->countryCode ?? null;
        $location->latitude    = $asset->latitude ?? null;
        $location->longitude   = $asset->longitude ?? null;

        return $this->createFromLocation($location);
    }

    protected function country(string $code, string $name): Country {
        $country = $this->countryResolver->get($code, $this->factory(function () use ($code, $name): Country {
            $country       = new Country();
            $normalizer    = $this->getNormalizer();
            $country->code = mb_strtoupper($normalizer->string($code));
            $country->name = $normalizer->string($name);

            $country->save();

            return $country;
        }));

        return $country;
    }

    protected function city(Country $country, string $name): City {
        $city = $this->cityResolver->get(
            $country,
            $name,
            $this->factory(function () use ($country, $name): City {
                $city          = new City();
                $normalizer    = $this->getNormalizer();
                $city->name    = $normalizer->string($name);
                $city->country = $country;

                $city->save();

                return $city;
            }),
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
    ): LocationModel {
        $created  = false;
        $factory  = $this->factory(
            function (
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
                $normalizer          = $this->getNormalizer();
                $location->country   = $country;
                $location->city      = $city;
                $location->postcode  = $normalizer->string($postcode);
                $location->state     = $normalizer->string($state);
                $location->line_one  = $normalizer->string($lineOne);
                $location->line_two  = $normalizer->string($lineTwo);
                $location->latitude  = $normalizer->coordinate($latitude);
                $location->longitude = $normalizer->coordinate($longitude);
                $location->geohash   = $this->geohash($latitude, $longitude);

                $location->save();

                return $location;
            },
        );
        $location = $this->locationResolver->get(
            $country,
            $city,
            $postcode,
            $lineOne,
            $lineTwo,
            static function () use ($factory): LocationModel {
                return $factory(new LocationModel());
            },
        );

        if (!$created && !$this->isSearchMode()) {
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
            $geohash    = (new Geotools())->geohash()->encode($coordinate, $length)->getGeohash();
        } catch (Throwable) {
            // empty
        }

        return $geohash;
    }
    //</editor-fold>
}
