<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Data\ProductLine;
use App\Services\DataLoader\Cache\Key;
use App\Services\DataLoader\Container\SingletonPersistent;
use App\Services\DataLoader\Resolver\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @extends Resolver<ProductLine>
 */
class ProductLineResolver extends Resolver implements SingletonPersistent {
    /**
     * @param Closure(?ProductLine): ProductLine|null $factory
     *
     * @return ($factory is null ? ProductLine|null : ProductLine)
     */
    public function get(string $key, Closure $factory = null): ?ProductLine {
        return $this->resolve($this->getUniqueKey($key), $factory);
    }

    protected function getPreloadedItems(): Collection {
        return ProductLine::query()->get();
    }

    protected function getFindQuery(): ?Builder {
        return ProductLine::query();
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
