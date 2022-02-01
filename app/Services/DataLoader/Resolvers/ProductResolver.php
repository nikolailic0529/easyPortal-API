<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Oem;
use App\Models\Product;
use App\Services\DataLoader\Cache\Key;
use App\Services\DataLoader\Container\SingletonPersistent;
use App\Services\DataLoader\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use JetBrains\PhpStorm\Pure;

class ProductResolver extends Resolver implements SingletonPersistent {
    public function get(Oem $oem, string $sku, Closure $factory = null): ?Product {
        return $this->resolve($this->getUniqueKey($oem, $sku), $factory);
    }

    /**
     * @param array<mixed> $keys
     */
    public function prefetch(array $keys, Closure|null $callback = null): static {
        return parent::prefetch($keys, $callback);
    }

    protected function getPreloadedItems(): Collection {
        return Product::query()->get();
    }

    protected function getFindQuery(): ?Builder {
        return Product::query();
    }

    public function getKey(Model $model): Key {
        return $model instanceof Product
            ? $this->getCacheKey($this->getUniqueKey($model->oem_id, $model->sku))
            : parent::getKey($model);
    }

    /**
     * @return array{oem_id: string, sku: string}
     */
    #[Pure]
    protected function getUniqueKey(Oem|string $oem, string $sku): array {
        return [
            'oem_id' => $oem instanceof Model ? $oem->getKey() : $oem,
            'sku'    => $sku,
        ];
    }
}
