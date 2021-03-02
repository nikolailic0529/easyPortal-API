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

use Psr\Log\LoggerInterface;

use function end;
use function explode;
use function mb_strtoupper;
use function reset;
use function sprintf;
use function str_contains;

class LocationFactory extends Factory {
    public function __construct(
        LoggerInterface $logger,
        Normalizer $normalizer,
        protected CountryProvider $countries,
        protected CityProvider $cities,
        protected LocationProvider $locations,
    ) {
        parent::__construct($logger, $normalizer);
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
        $country = $this->country('??', 'Unknown Country');

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
        $model = $this->location(
            $country,
            $city,
            $location->zip,
            $location->address,
            '',
            $state,
        );

        // Return
        return $model;
    }

    protected function country(string $code, string $name): Country {
        $country = $this->countries->get($code, function () use ($code, $name): Country {
            $country       = new Country();
            $country->code = mb_strtoupper($this->normalizer->string($code));
            $country->name = $this->normalizer->string($name);

            $country->save();

            return $country;
        });

        return $country;
    }

    protected function city(Country $country, string $name): City {
        $city = $this->cities->get(
            $country,
            $name,
            function () use ($country, $name): City {
                $city          = new City();
                $city->name    = $this->normalizer->string($name);
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
    ): LocationModel {
        $location = $this->locations->get(
            $country,
            $city,
            $postcode,
            $lineOne,
            $lineTwo,
            function () use ($country, $city, $postcode, $lineOne, $lineTwo, $state): LocationModel {
                $location           = new LocationModel();
                $location->country  = $country;
                $location->city     = $city;
                $location->postcode = $this->normalizer->string($postcode);
                $location->state    = $this->normalizer->string($state);
                $location->line_one = $this->normalizer->string($lineOne);
                $location->line_two = $this->normalizer->string($lineTwo);

                $location->save();

                return $location;
            },
        );

        if ($location->state === '') {
            $location->state = $this->normalizer->string($state);

            $location->save();
        }

        return $location;
    }
}
