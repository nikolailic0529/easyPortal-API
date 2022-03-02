<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\City;
use App\Models\Country;
use App\Services\DataLoader\Cache\Key;
use App\Services\DataLoader\Container\SingletonPersistent;
use App\Services\DataLoader\Resolver\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @extends \App\Services\DataLoader\Resolver\Resolver<\App\Models\City>
 */
class CityResolver extends Resolver implements SingletonPersistent {
    public function get(Country $country, string $key, Closure $factory = null): ?City {
        return $this->resolve($this->getUniqueKey($country, $key), $factory);
    }

    protected function getPreloadedItems(): Collection {
        return City::query()->get();
    }

    protected function getFindQuery(): ?Builder {
        return City::query();
    }

    public function getKey(Model $model): Key {
        return $model instanceof City
            ? $this->getCacheKey($this->getUniqueKey($model->country_id, $model->key))
            : parent::getKey($model);
    }

    /**
     * @return array{country_id: string, key: string}
     */
    protected function getUniqueKey(Country|string $country, string $key): array {
        return [
            'country_id' => $country instanceof Model ? $country->getKey() : $country,
            'key'        => $key,
        ];
    }
}
