<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Country;
use App\Services\DataLoader\Cache\Key;
use App\Services\DataLoader\Container\SingletonPersistent;
use App\Services\DataLoader\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CountryResolver extends Resolver implements SingletonPersistent {
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
        return $model instanceof Country
            ? $this->getCacheKey($this->getUniqueKey($model->code))
            : parent::getKey($model);
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
