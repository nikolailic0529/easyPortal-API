<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Data\City;
use App\Models\Data\Country;
use App\Models\Data\Location;
use App\Services\DataLoader\Cache\Key;
use App\Services\DataLoader\Resolver\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * @extends Resolver<Location>
 */
class LocationResolver extends Resolver {
    /**
     * @param Closure(?Location): Location|null $factory
     *
     * @return ($factory is null ? Location|null : Location)
     */
    public function get(
        Country $country,
        City $city,
        string $postcode,
        string $lineOne,
        string $lineTwo,
        Closure $factory = null,
    ): ?Location {
        return $this->resolve(
            $this->getUniqueKey($country, $city, $postcode, $lineOne, $lineTwo),
            $factory,
        );
    }

    protected function getFindQuery(): ?Builder {
        return Location::query();
    }

    protected function getFindWhereProperty(Builder $builder, string $property, ?string $value): Builder {
        switch ($property) {
            case 'line':
                $builder = $builder->where(
                    DB::raw("CONCAT(`line_one`, IF(`line_two` != '', CONCAT(' ', `line_two`), ''))"),
                    '=',
                    $value,
                );
                break;
            default:
                $builder = parent::getFindWhereProperty($builder, $property, $value);
                break;
        }

        return $builder;
    }

    public function getKey(Model $model): Key {
        return $this->getCacheKey($this->getUniqueKey(
            $model->country_id,
            $model->city_id,
            $model->postcode,
            $model->line_one,
            $model->line_two,
        ));
    }

    /**
     * @return array{country_id:string, city_id:string, postcode:string, line:string}
     */
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
