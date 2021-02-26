<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Providers;

use App\Models\City;
use App\Models\Country;
use App\Models\Model;
use App\Services\DataLoader\Cache\ClosureKey;
use App\Services\DataLoader\Provider;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use JetBrains\PhpStorm\Pure;

class CityProvider extends Provider {
    public function get(Country $country, string $name, Closure $factory): City {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve($this->getUniqueKey($country, $name), $factory);
    }

    protected function getInitialQuery(): ?Builder {
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
            ] + parent::getKeyRetrievers();
    }

    /**
     * @return array{country: string, name: string}
     */
    #[Pure]
    protected function getUniqueKey(Country|string $country, string $name): array {
        return [
            'country' => $country instanceof Model ? $country->getKey() : $country,
            'name'    => $name,
        ];
    }
}
