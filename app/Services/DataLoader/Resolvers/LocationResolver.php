<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\City;
use App\Models\Country;
use App\Models\Location;
use App\Services\DataLoader\Cache\ClosureKey;
use App\Services\DataLoader\Resolver;
use App\Utils\Eloquent\Model;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use JetBrains\PhpStorm\Pure;

class LocationResolver extends Resolver {
    public function get(
        Country $country,
        City $city,
        string $postcode,
        string $lineOne,
        string $lineTwo,
        Closure $factory = null,
    ): ?Location {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve(
            $this->getUniqueKey($country, $city, $postcode, $lineOne, $lineTwo),
            $factory,
        );
    }

    /**
     * @param \App\Models\Location
     *      |\Illuminate\Support\Collection<\App\Models\Location>
     *      |array<\App\Models\Location> $object
     */
    public function add(Location|Collection|array $object): void {
        parent::put($object);
    }

    /**
     * @param array<mixed> $keys
     */
    public function prefetch(array $keys, bool $reset = false, Closure|null $callback = null): static {
        return parent::prefetch($keys, $reset, $callback);
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

    /**
     * @inheritdoc
     */
    protected function getKeyRetrievers(): array {
        return [
            'unique' => new ClosureKey($this->normalizer, function (Location $location): array {
                return $this->getUniqueKey(
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
