<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Data\Oem;
use App\Models\Data\Product;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Resolver\Resolvers\ProductResolver;
use Carbon\CarbonImmutable;

/**
 * @mixin Factory
 */
trait WithProduct {
    abstract protected function getProductResolver(): ProductResolver;

    protected function product(
        Oem $oem,
        string $sku,
        ?string $name,
        ?CarbonImmutable $eol,
        ?CarbonImmutable $eos,
    ): Product {
        return $this->getProductResolver()->get(
            $oem,
            $sku,
            static function (?Product $product) use ($oem, $sku, $name, $eol, $eos): Product {
                $product    ??= new Product();
                $product->oem = $oem;
                $product->sku = $sku;
                $product->eol = $eol;
                $product->eos = $eos;

                if (!$product->exists || !$product->name) {
                    // Product name may be inconsistent, eg
                    // - 'HPE Hardware Maintenance Onsite Support'
                    // - '(Gewährleistung) HPE Hardware Maintenance Onsite Support'
                    //
                    // To avoid infinite updates we will not update it at all.
                    $product->name = (string) $name;
                }

                $product->save();

                return $product;
            },
        );
    }
}
