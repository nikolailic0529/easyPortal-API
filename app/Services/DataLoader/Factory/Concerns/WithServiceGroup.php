<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Oem;
use App\Models\ServiceGroup;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\ServiceGroupResolver;

/**
 * @mixin Factory
 */
trait WithServiceGroup {
    abstract protected function getNormalizer(): Normalizer;

    abstract protected function getServiceGroupResolver(): ServiceGroupResolver;

    protected function serviceGroup(Oem $oem, string $sku): ?ServiceGroup {
        // Null?
        $sku = $this->getNormalizer()->string($sku) ?: null;

        if ($sku === null) {
            return null;
        }

        // Find/Create
        return $this->getServiceGroupResolver()->get($oem, $sku, $this->factory(
            static function () use ($oem, $sku): ServiceGroup {
                $group       = new ServiceGroup();
                $group->key  = "{$oem->getTranslatableKey()}/{$sku}";
                $group->oem  = $oem;
                $group->sku  = $sku;
                $group->name = $sku;

                $group->save();

                return $group;
            },
        ));
    }
}
