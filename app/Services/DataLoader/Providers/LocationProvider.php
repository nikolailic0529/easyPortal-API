<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Providers;

use App\Models\City;
use App\Models\Country;
use App\Models\Location;
use App\Models\Model;
use App\Services\DataLoader\Cache\ClosureKey;
use App\Services\DataLoader\Provider;
use Illuminate\Support\Facades\DB;
use JetBrains\PhpStorm\Pure;

class LocationProvider extends Provider {
    public function get(
        Country $country,
        City $city,
        string $postcode,
        string $state,
        string $lineOne,
        string $lineTwo = '',
    ): Location {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve(
            $this->getUniqueKey($country, $city, $postcode, $lineOne, $lineTwo),
            function () use ($country, $city, $postcode, $state, $lineOne, $lineTwo) {
                return $this->find($country, $city, $postcode, $state, $lineOne, $lineTwo);
            },
            function () use ($country, $city, $postcode, $state, $lineOne, $lineTwo): Model {
                return $this->create($country, $city, $postcode, $state, $lineOne, $lineTwo);
            },
        );
    }

    protected function find(
        Country $country,
        City $city,
        string $postcode,
        string $state,
        string $lineOne,
        string $lineTwo,
    ): ?Location {
        $key = $this->getUniqueKey($country, $city, $postcode, $lineOne, $lineTwo);
        $key = $this->normalizer->key($key);

        return Location::query()
            ->where('country_id', '=', $key['country_id'])
            ->where('city_id', '=', $key['city_id'])
            ->where('postcode', '=', $key['postcode'])
            ->where(
                DB::raw("CONCAT(`line_one`, IF(`line_two` != '', CONCAT(' ', `line_two`), ''))"),
                '=',
                $key['line'],
            )
            ->first();
    }

    protected function create(
        Country $country,
        City $city,
        string $postcode,
        string $state,
        string $lineOne,
        string $lineTwo,
    ): Location {
        $location           = new Location();
        $location->country  = $country;
        $location->city     = $city;
        $location->postcode = $this->normalizer->string($postcode);
        $location->state    = $this->normalizer->string($state);
        $location->line_one = $this->normalizer->string($lineOne);
        $location->line_two = $this->normalizer->string($lineTwo);

        $location->save();

        return $location;
    }

    /**
     * @inheritdoc
     */
    protected function getKeyRetrievers(): array {
        return [
                'unique' => new ClosureKey(function (Location $location): array {
                    return $this->getUniqueKey(
                        $location->country_id,
                        $location->city_id,
                        $location->postcode,
                        $location->line_one,
                        $location->line_two,
                    );
                }),
            ] + parent::getKeyRetrievers();
    }

    /**
     * @return array{country_id:string, city_id:string, postcode:string, line_one:string, line_two:string}
     */
    #[Pure]
    protected function getUniqueKey(
        Country|string $country,
        City|string $city,
        string $postcode,
        string $lineOne,
        string $lineTwo,
    ): array {
        return [
            'country_id' => $country instanceof Model ? $country->getKey() : $country,
            'city_id'    => $city instanceof Model ? $city->getKey() : $city,
            'postcode'   => $postcode,
            'line'       => "{$lineOne} {$lineTwo}",
        ];
    }
}
