<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Enums\ProductType;
use App\Models\Model;
use App\Models\Oem;
use App\Models\Product;
use App\Services\DataLoader\Cache\ClosureKey;
use App\Services\DataLoader\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use JetBrains\PhpStorm\Pure;

class ProductResolver extends Resolver {
    public function get(ProductType $type, Oem $oem, string $sku, Closure $factory = null): ?Product {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve($this->getUniqueKey($type, $oem, $sku), $factory);
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
                return $this->getUniqueKey($product->type, $product->oem_id, $product->sku);
            }),
        ];
    }

    /**
     * @return array{oem: string, sku: string}
     */
    #[Pure]
    protected function getUniqueKey(ProductType $type, Oem|string $oem, string $sku): array {
        return [
            'type'   => $type->getValue(),
            'oem_id' => $oem instanceof Model ? $oem->getKey() : $oem,
            'sku'    => $sku,
        ];
    }
}
