<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Data\Country;
use App\Services\DataLoader\Cache\Key;
use App\Services\DataLoader\Container\SingletonPersistent;
use App\Services\DataLoader\Resolver\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @extends Resolver<Country>
 */
class CountryResolver extends Resolver implements SingletonPersistent {
    /**
     * @param Closure(): Country|null $factory
     *
     * @return ($factory is null ? Country|null : Country)
     */
    public function get(string $code, Closure $factory = null): ?Country {
        return $this->resolve($this->getUniqueKey($code), $factory);
    }

    protected function getPreloadedItems(): Collection {
        return Country::query()->get();
    }

    protected function getFindQuery(): ?Builder {
        return Country::query();
    }

    public function getKey(Model $model): Key {
        return $this->getCacheKey($this->getUniqueKey($model->code));
    }

    /**
     * @return array{code: string}
     */
    protected function getUniqueKey(string $code): array {
        return [
            'code' => $code,
        ];
    }
}
