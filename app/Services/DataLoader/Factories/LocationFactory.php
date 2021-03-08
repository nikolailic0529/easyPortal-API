<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\City;
use App\Models\Country;
use App\Models\Location as LocationModel;
use App\Models\Model;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Providers\CityProvider;
use App\Services\DataLoader\Providers\CountryProvider;
use App\Services\DataLoader\Providers\LocationProvider;
use App\Services\DataLoader\Schema\Asset;
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

/**
 * @internal
 */
class LocationFactory extends DependentModelFactory {
    public function __construct(
        LoggerInterface $logger,
        Normalizer $normalizer,
        protected CountryProvider $countries,
        protected CityProvider $cities,
        protected LocationProvider $locations,
    ) {
        parent::__construct($logger, $normalizer);
    }

    public function find(Model $object, Type $type): ?LocationModel {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return parent::find($object, $type);
    }

    public function create(Model $object, Type $type): ?LocationModel {
        $model = null;

        if ($type instanceof Location) {
            $model = $this->createFromLocation($object, $type);
        } elseif ($type instanceof Asset) {
            $model = $this->createFromAsset($object, $type);
        } else {
            throw new InvalidArgumentException(sprintf(
                'The `$type` must be instance of `%s`.',
                Location::class,
            ));
        }

        return $model;
    }

    protected function createFromLocation(Model $object, Location $location): ?LocationModel {
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
        $object = $this->location(
            $object,
            $country,
            $city,
            $location->zip,
            $location->address,
            '',
            $state,
        );

        // Return
        return $object;
    }

    protected function createFromAsset(Model $object, Asset $asset): ?LocationModel {
        // TODO [DataLoader] Today Asset::address2 is always equal `TODO` (I
        //      guess because it the same as the customer's location that
        //      doesn't have it), so we ignore it. We will need review this
        //      method and tests when it will filled correctly.
        $location          = new Location();
        $location->zip     = $asset->zip;
        $location->city    = $asset->city;
        $location->address = $asset->address;

        return $this->createFromLocation($object, $location);
    }

    protected function country(string $code, string $name): Country {
        $country = $this->countries->get($code, $this->factory(function () use ($code, $name): Country {
            $country       = new Country();
            $country->code = mb_strtoupper($this->normalizer->string($code));
            $country->name = $this->normalizer->string($name);

            $country->save();

            return $country;
        }));

        return $country;
    }

    protected function city(Country $country, string $name): City {
        $city = $this->cities->get(
            $country,
            $name,
            $this->factory(function () use ($country, $name): City {
                $city          = new City();
                $city->name    = $this->normalizer->string($name);
                $city->country = $country;

                $city->save();

                return $city;
            }),
        );

        return $city;
    }

    protected function location(
        Model $object,
        Country $country,
        City $city,
        string $postcode,
        string $lineOne,
        string $lineTwo,
        string $state,
    ): LocationModel {
        $created  = false;
        $factory  = $this->factory(
            function (
                LocationModel $location,
            ) use (
                &$created,
                $object,
                $country,
                $city,
                $postcode,
                $lineOne,
                $lineTwo,
                $state,
            ): LocationModel {
                $created               = !$location->exists;
                $location->object_type = $object->getMorphClass();
                $location->object_id   = $object->getKey();
                $location->country     = $country;
                $location->city        = $city;
                $location->postcode    = $this->normalizer->string($postcode);
                $location->state       = $this->normalizer->string($state);
                $location->line_one    = $this->normalizer->string($lineOne);
                $location->line_two    = $this->normalizer->string($lineTwo);

                $location->save();

                return $location;
            },
        );
        $location = $this->locations->get(
            $object,
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
}
