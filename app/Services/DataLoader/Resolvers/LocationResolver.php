<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\City;
use App\Models\Country;
use App\Models\Location;
use App\Models\Model;
use App\Services\DataLoader\Cache\ClosureKey;
use App\Services\DataLoader\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use JetBrains\PhpStorm\Pure;

class LocationResolver extends Resolver {
    public function get(
        Model $model,
        Country $country,
        City $city,
        string $postcode,
        string $lineOne,
        string $lineTwo,
        Closure $factory = null,
    ): ?Location {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve(
            $this->getUniqueKey($model, $country, $city, $postcode, $lineOne, $lineTwo),
            $factory,
        );
    }

    protected function getFindQuery(): ?Builder {
        return Location::query();
    }

    protected function getFindWhere(Builder $builder, mixed $key): Builder {
        return $builder
            ->where('object_type', '=', $key['object_type'])
            ->where('object_id', '=', $key['object_id'])
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
                    $location,
                    $location->country_id,
                    $location->city_id,
                    $location->postcode,
                    $location->line_one,
                    $location->line_two,
                );
            }),
        ];
    }

    /**
     * @return array{country_id:string, city_id:string, postcode:string, line_one:string, line_two:string}
     */
    #[Pure]
    protected function getUniqueKey(
        Model|Location $model,
        Country|string $country,
        City|string $city,
        string $postcode,
        string $lineOne,
        string $lineTwo,
    ): array {
        return ($model instanceof Location
                ? ['object_type' => $model->object_type, 'object_id' => $model->object_id]
                : ['object_type' => $model->getMorphClass(), 'object_id' => $model->getKey()]
            ) + [
                'country_id' => $country instanceof Model ? $country->getKey() : $country,
                'city_id'    => $city instanceof Model ? $city->getKey() : $city,
                'postcode'   => $postcode,
                'line'       => "{$lineOne} {$lineTwo}",
            ];
    }
}
