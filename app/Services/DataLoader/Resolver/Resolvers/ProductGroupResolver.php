<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Data\ProductGroup;
use App\Services\DataLoader\Cache\Key;
use App\Services\DataLoader\Container\SingletonPersistent;
use App\Services\DataLoader\Resolver\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @extends Resolver<ProductGroup>
 */
class ProductGroupResolver extends Resolver implements SingletonPersistent {
    /**
     * @param Closure(): ProductGroup|null $factory
     *
     * @return ($factory is null ? ProductGroup|null : ProductGroup)
     */
    public function get(string $key, Closure $factory = null): ?ProductGroup {
        return $this->resolve($this->getUniqueKey($key), $factory);
    }

    protected function getPreloadedItems(): Collection {
        return ProductGroup::query()->get();
    }

    protected function getFindQuery(): ?Builder {
        return ProductGroup::query();
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
