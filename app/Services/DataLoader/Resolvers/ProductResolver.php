<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Oem;
use App\Models\Product;
use App\Services\DataLoader\Cache\ClosureKey;
use App\Services\DataLoader\Container\SingletonPersistent;
use App\Services\DataLoader\Resolver;
use App\Utils\Eloquent\Model;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use JetBrains\PhpStorm\Pure;

class ProductResolver extends Resolver implements SingletonPersistent {
    public function get(Oem $oem, string $sku, Closure $factory = null): ?Product {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve($this->getUniqueKey($oem, $sku), $factory);
    }

    /**
     * @param array<mixed> $keys
     */
    public function prefetch(array $keys, bool $reset = false, Closure|null $callback = null): static {
        return parent::prefetch($keys, $reset, $callback);
    }

    protected function getPreloadedItems(): Collection {
        return Product::query()->get();
    }

    protected function getFindQuery(): ?Builder {
        return Product::query();
    }

    /**
     * @inheritdoc
     */
    protected function getKeyRetrievers(): array {
        return [
            'unique' => new ClosureKey(function (Product $product): array {
                return $this->getUniqueKey($product->oem_id, $product->sku);
            }),
        ];
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
