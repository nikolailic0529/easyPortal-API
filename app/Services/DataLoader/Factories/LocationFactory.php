<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\City;
use App\Models\Country;
use App\Models\Location as LocationModel;
use App\Services\DataLoader\Factory;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Providers\CityProvider;
use App\Services\DataLoader\Providers\CountryProvider;
use App\Services\DataLoader\Providers\LocationProvider;
use App\Services\DataLoader\Schema\Location;
use App\Services\DataLoader\Schema\Type;
use InvalidArgumentException;

use function end;
use function explode;
use function mb_strtoupper;
use function reset;
use function sprintf;
use function str_contains;

class LocationFactory implements Factory {
    public function __construct(
        protected CountryProvider $countries,
        protected CityProvider $cities,
        protected LocationProvider $locations,
    ) {
        // empty
    }

    public function create(Type $type): LocationModel {
        $model = null;

        if ($type instanceof Location) {
            $model = $this->createFromLocation($type);
        } else {
            throw new InvalidArgumentException(sprintf(
                'The `$type` must be instance of `%s`.',
                Location::class,
            ));
        }

        return $model;
    }

    protected function createFromLocation(Location $location): LocationModel {
        // Country is not yet available so we use Unknown
        $country = $this->countries->get('??', function (?Country $country, Normalizer $normalizer): Country {
            return $this->country($country, $normalizer, '??', 'Unknown Country');
        });

        // City may contains State
        $city  = null;
        $state = '';

        if (str_contains($location->city, ',')) {
            $parts = explode($location->city, ',', 2);
            $state = end($parts);
            $city  = reset($parts);
        } else {
            $city = $location->city;
        }

        // City
        $city = $this->cities->get(
            $country,
            $city,
            function (?City $model, Normalizer $normalizer) use ($country, $city): City {
                return $this->city($model, $normalizer, $country, $city);
            },
        );

        // Location
        $model = $this->locations->get(
            $country,
            $city,
            $location->zip,
            $location->address,
            '',
            function (
                ?LocationModel $model,
                Normalizer $normalizer,
            ) use (
                $country,
                $city,
                $location,
                $state,
            ): LocationModel {
                return $this->location(
                    $model,
                    $normalizer,
                    $country,
                    $city,
                    $location->zip,
                    $location->address,
                    '',
                    $state,
                );
            },
        );

        // Return
        return $model;
    }

    protected function country(?Country $country, Normalizer $normalizer, string $code, string $name): Country {
        if (!$country) {
            $country       = new Country();
            $country->code = mb_strtoupper($normalizer->string($code));
            $country->name = $normalizer->string($name);

            $country->save();
        }

        return $country;
    }

    protected function city(?City $city, Normalizer $normalizer, Country $country, string $name): City {
        if (!$city) {
            $city          = new City();
            $city->name    = $normalizer->string($name);
            $city->country = $country;

            $city->save();
        }

        return $city;
    }

    protected function location(
        ?LocationModel $location,
        Normalizer $normalizer,
        Country $country,
        City $city,
        string $postcode,
        string $lineOne,
        string $lineTwo,
        string $state,
    ): LocationModel {
        if ($location) {
            if ($location->state === '') {
                $location->state = $normalizer->string($state);

                $location->save();
            }
        } else {
            $location           = new LocationModel();
            $location->country  = $country;
            $location->city     = $city;
            $location->postcode = $normalizer->string($postcode);
            $location->state    = $normalizer->string($state);
            $location->line_one = $normalizer->string($lineOne);
            $location->line_two = $normalizer->string($lineTwo);

            $location->save();
        }

        return $location;
    }
}
