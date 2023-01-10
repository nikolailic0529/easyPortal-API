<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Data\City;
use App\Models\Data\Country;
use App\Models\Data\Location as LocationModel;
use App\Services\DataLoader\Resolver\Resolvers\CityResolver;
use App\Services\DataLoader\Resolver\Resolvers\CountryResolver;
use App\Services\DataLoader\Resolver\Resolvers\LocationResolver;
use App\Services\DataLoader\Schema\Types\Location;
use League\Geotools\Coordinate\Coordinate;
use League\Geotools\Geohash\Geohash;
use Throwable;

use function explode;
use function mb_strtoupper;
use function str_contains;
use function trim;

trait WithLocations {
    abstract protected function getLocationResolver(): LocationResolver;

    abstract protected function getCountryResolver(): CountryResolver;

    abstract protected function getCityResolver(): CityResolver;

    protected function isLocationEmpty(Location $location): bool {
        return !($location->zip ?? null) || !($location->city ?? null);
    }

    protected function location(Location $location, bool $update = true): ?LocationModel {
        // Is empty?
        if ($this->isLocationEmpty($location)) {
            return null;
        }

        // Country may be unknown so we use Unknown
        $country = isset($location->countryCode)
            ? $this->country($location->countryCode, $location->country)
            : $this->country(self::getUnknownCountryCode(), self::getUnknownCountryName());

        // City? (it may contain State)
        $city  = $location->city;
        $state = '';

        if ($city && str_contains($city, ',')) {
            [$city, $state] = explode(',', $city, 2);
            $state          = trim($state);
        }

        if (!$city) {
            return null;
        }

        // Location
        $city     = $this->city($country, $city);
        $created  = false;
        $factory  = function (LocationModel $model) use (&$created, $country, $city, $location, $state): LocationModel {
            $created          = !$model->exists;
            $model->country   = $country;
            $model->city      = $city;
            $model->postcode  = (string) $location->zip;
            $model->state     = $state;
            $model->line_one  = (string) $location->address;
            $model->line_two  = '';
            $model->latitude  = $location->latitude;
            $model->longitude = $location->longitude;
            $model->geohash   = $this->geohash($location->latitude, $location->longitude);

            $model->save();

            return $model;
        };
        $location = $this->getLocationResolver()->get(
            $country,
            $city,
            (string) $location->zip,
            (string) $location->address,
            '',
            static function () use ($factory): LocationModel {
                return $factory(new LocationModel());
            },
        );

        if (!$created && $update) {
            $factory($location);
        }

        // Return
        return $location;
    }

    protected function country(string $code, ?string $name): Country {
        // Get/Create
        $created = false;
        $factory = static function (Country $country) use (&$created, $code, $name): Country {
            $created = !$country->exists;
            $code    = mb_strtoupper($code);

            if ($created || $country->name === self::getUnknownCountryName() || $country->name === $code) {
                $country->code = $code;
                $country->name = $name ?: $code;

                $country->save();
            }

            return $country;
        };
        $country = $this->getCountryResolver()->get(
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
        $city = $this->getCityResolver()->get(
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

    private static function getUnknownCountryCode(): string {
        return '??';
    }

    private static function getUnknownCountryName(): string {
        return 'Unknown Country';
    }
}
