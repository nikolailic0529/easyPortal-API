<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Oem;
use App\Services\DataLoader\Cache\Key;
use App\Services\DataLoader\Container\SingletonPersistent;
use App\Services\DataLoader\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class OemResolver extends Resolver implements SingletonPersistent {
    public function get(string $key, Closure $factory = null): ?Oem {
        return $this->resolve($this->getUniqueKey($key), $factory);
    }

    protected function getPreloadedItems(): Collection {
        return Oem::query()->get();
    }

    public function getKey(Model $model): Key {
        return $model instanceof Oem
            ? $this->getCacheKey($this->getUniqueKey($model->key))
            : parent::getKey($model);
    }

    /**
     * @return array{key: string}
     */
    protected function getUniqueKey(string $key): array {
        return [
            'key' => $key,
        ];
    }
}
