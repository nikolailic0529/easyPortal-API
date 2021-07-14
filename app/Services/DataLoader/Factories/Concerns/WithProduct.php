<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Enums\ProductType;
use App\Models\Oem;
use App\Models\Product;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\ProductResolver;

/**
 * @mixin \App\Services\DataLoader\Factory
 */
trait WithProduct {
    abstract protected function getNormalizer(): Normalizer;

    abstract protected function getProductResolver(): ProductResolver;

    protected function product(
        Oem $oem,
        ProductType $type,
        string $sku,
        string $name,
        ?string $eol,
        ?string $eos,
    ): Product {
        // Get/Create
        $created = false;
        $factory = $this->factory(
            function (Product $product) use (&$created, $type, $oem, $sku, $name, $eol, $eos): Product {
                $created       = !$product->exists;
                $normalizer    = $this->getNormalizer();
                $product->type = $type;
                $product->oem  = $oem;
                $product->sku  = $normalizer->string($sku);
                $product->eol  = $normalizer->datetime($eol);
                $product->eos  = $normalizer->datetime($eos);

                if ($created) {
                    // Product name may be inconsistent, eg
                    // - 'HPE Hardware Maintenance Onsite Support'
                    // - '(Gewährleistung) HPE Hardware Maintenance Onsite Support'
                    //
                    // To avoid infinite updates we will not update it at all.
                    $product->name = $normalizer->string($name);
                }

                $product->save();

                return $product;
            },
        );
        $product = $this->getProductResolver()->get(
            $type,
            $oem,
            $sku,
            static function () use ($factory): Product {
                return $factory(new Product());
            },
        );

        // Update
        if (!$created && !$this->isSearchMode()) {
            $factory($product);
        }

        // Return
        return $product;
    }
}
