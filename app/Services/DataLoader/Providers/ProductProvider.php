<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Providers;

use App\Models\Model;
use App\Models\Oem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\DataLoader\Cache\ClosureKey;
use App\Services\DataLoader\Provider;
use Illuminate\Database\Eloquent\Builder;
use JetBrains\PhpStorm\Pure;

class ProductProvider extends Provider {
    public function get(Oem $oem, string $sku, ProductCategory $category, string $name): Product {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve(
            $this->getUniqueKey($oem, $sku),
            function () use ($oem, $sku, $category, $name): Model {
                return $this->create($oem, $sku, $category, $name);
            },
        );
    }

    protected function getFindQuery(mixed $key): ?Builder {
        return Product::query()
            ->where('oem_id', '=', $key['oem'])
            ->where('sku', '=', $key['sku']);
    }

    protected function create(Oem $oem, string $sku, ProductCategory $category, string $name): Product {
        $product           = new Product();
        $product->oem      = $oem;
        $product->sku      = $this->normalizer->string($sku);
        $product->name     = $this->normalizer->string($name);
        $product->category = $category;

        $product->save();

        return $product;
    }

    /**
     * @inheritdoc
     */
    protected function getKeyRetrievers(): array {
        return [
                'unique' => new ClosureKey(function (Product $product): array {
                    return $this->getUniqueKey($product->oem_id, $product->sku);
                }),
            ] + parent::getKeyRetrievers();
    }

    /**
     * @return array{oem: string, sku: string}
     */
    #[Pure]
    protected function getUniqueKey(Oem|string $oem, string $sku): array {
        return [
            'oem' => $oem instanceof Model ? $oem->getKey() : $oem,
            'sku' => $sku,
        ];
    }
}
