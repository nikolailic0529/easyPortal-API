<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Country;
use App\Services\DataLoader\Cache\ClosureKey;
use App\Services\DataLoader\Resolver;
use Closure;
use Illuminate\Support\Collection;

class CountryResolver extends Resolver {
    public function get(string $code, Closure $factory = null): ?Country {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve($code, $factory);
    }

    protected function getPreloadedItems(): Collection {
        return Country::query()->get();
    }

    /**
     * @inheritdoc
     */
    protected function getKeyRetrievers(): array {
        return [
            'code' => new ClosureKey(static function (Country $country): string {
                return $country->code;
            }),
        ];
    }
}
