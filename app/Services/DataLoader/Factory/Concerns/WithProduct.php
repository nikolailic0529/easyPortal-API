<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Data\Oem;
use App\Models\Data\Product;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\ProductResolver;
use Carbon\CarbonImmutable;

/**
 * @mixin Factory
 */
trait WithProduct {
    abstract protected function getNormalizer(): Normalizer;

    abstract protected function getProductResolver(): ProductResolver;

    protected function product(
        Oem $oem,
        string $sku,
        ?string $name,
        ?CarbonImmutable $eol,
        ?CarbonImmutable $eos,
    ): Product {
        // Get/Create
        $sku     = $this->getNormalizer()->string($sku);
        $created = false;
        $factory = function (Product $product) use (&$created, $oem, $sku, $name, $eol, $eos): Product {
            $created      = !$product->exists;
            $normalizer   = $this->getNormalizer();
            $product->oem = $oem;
            $product->sku = $sku;
            $product->eol = $eol;
            $product->eos = $eos;

            if ($created || !$product->name) {
                // Product name may be inconsistent, eg
                // - 'HPE Hardware Maintenance Onsite Support'
                // - '(Gewährleistung) HPE Hardware Maintenance Onsite Support'
                //
                // To avoid infinite updates we will not update it at all.
                $product->name = (string) $normalizer->string($name);
            }

            $product->save();

            return $product;
        };
        $product = $this->getProductResolver()->get(
            $oem,
            $sku,
            static function () use ($factory): Product {
                return $factory(new Product());
            },
        );

        // Update
        if (!$created) {
            $factory($product);
        }

        // Return
        return $product;
    }
}
