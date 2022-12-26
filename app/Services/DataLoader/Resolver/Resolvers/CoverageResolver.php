<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Data\Coverage;
use App\Services\DataLoader\Cache\Key;
use App\Services\DataLoader\Container\SingletonPersistent;
use App\Services\DataLoader\Resolver\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @extends Resolver<Coverage>
 */
class CoverageResolver extends Resolver implements SingletonPersistent {
    /**
     * @param Closure(): Coverage|null $factory
     *
     * @return ($factory is null ? Coverage|null : Coverage)
     */
    public function get(string $key, Closure $factory = null): ?Coverage {
        return $this->resolve($this->getUniqueKey($key), $factory);
    }

    protected function getPreloadedItems(): Collection {
        return Coverage::query()->get();
    }

    protected function getFindQuery(): ?Builder {
        return Coverage::query();
    }

    public function getKey(Model $model): Key {
        return $this->getCacheKey($this->getUniqueKey($model->key));
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
