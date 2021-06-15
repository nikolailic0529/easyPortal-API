<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\City;
use App\Models\Country;
use App\Models\Model;
use App\Services\DataLoader\Cache\ClosureKey;
use App\Services\DataLoader\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use JetBrains\PhpStorm\Pure;

class CityResolver extends Resolver {
    public function get(Country $country, string $name, Closure $factory = null): ?City {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve($this->getUniqueKey($country, $name), $factory);
    }

    protected function getPreloadedItems(): Collection {
        return City::query()->get();
    }

    protected function getFindQuery(): ?Builder {
        return City::query();
    }

    /**
     * @inheritdoc
     */
    protected function getKeyRetrievers(): array {
        return [
            'unique' => new ClosureKey(function (City $city): array {
                return $this->getUniqueKey($city->country_id, $city->name);
            }),
        ];
    }

    /**
     * @return array{country: string, name: string}
     */
    #[Pure]
    protected function getUniqueKey(Country|string $country, string $name): array {
        return [
            'country_id' => $country instanceof Model ? $country->getKey() : $country,
            'name'       => $name,
        ];
    }
}
