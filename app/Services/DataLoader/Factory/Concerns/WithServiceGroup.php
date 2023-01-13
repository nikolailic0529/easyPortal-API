<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Data\Oem;
use App\Models\Data\ServiceGroup;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Resolver\Resolvers\ServiceGroupResolver;

/**
 * @mixin Factory
 */
trait WithServiceGroup {
    abstract protected function getServiceGroupResolver(): ServiceGroupResolver;

    protected function serviceGroup(Oem $oem, string $sku, string $name = null): ?ServiceGroup {
        // Empty?
        if ($sku === '') {
            return null;
        }

        // Find/Create
        return $this->getServiceGroupResolver()->get(
            $oem,
            $sku,
            static function (?ServiceGroup $group) use ($oem, $sku, $name): ServiceGroup {
                $group ??= new ServiceGroup();

                if (!$group->exists) {
                    $group->key = "{$oem->getTranslatableKey()}/{$sku}";
                    $group->oem = $oem;
                    $group->sku = $sku;
                }

                if (!$group->name || $group->name === $sku) {
                    $group->name = $name ?: $sku;
                }

                $group->save();

                return $group;
            },
        );
    }
}
