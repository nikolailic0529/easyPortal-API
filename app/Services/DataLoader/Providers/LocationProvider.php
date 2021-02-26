<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Providers;

use App\Models\City;
use App\Models\Country;
use App\Models\Location;
use App\Models\Model;
use App\Services\DataLoader\Cache\ClosureKey;
use App\Services\DataLoader\Provider;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use JetBrains\PhpStorm\Pure;

class LocationProvider extends Provider {
    public function get(
        Country $country,
        City $city,
        string $postcode,
        string $lineOne,
        string $lineTwo,
        Closure $factory,
    ): Location {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve(
            $this->getUniqueKey($country, $city, $postcode, $lineOne, $lineTwo),
            $factory,
        );
    }

    protected function getFindQuery(mixed $key): ?Builder {
        return Location::query()
            ->where('country_id', '=', $key['country_id'])
            ->where('city_id', '=', $key['city_id'])
            ->where('postcode', '=', $key['postcode'])
            ->where(
                DB::raw("CONCAT(`line_one`, IF(`line_two` != '', CONCAT(' ', `line_two`), ''))"),
                '=',
                $key['line'],
            );
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
