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
        $created = false;
        $factory = static function (ServiceGroup $group) use (&$created, $oem, $sku, $name): ServiceGroup {
            $created = !$group->exists;

            if ($created) {
                $group->key = "{$oem->getTranslatableKey()}/{$sku}";
                $group->oem = $oem;
                $group->sku = $sku;
            }

            if (!$group->name || $group->name === $sku) {
                $group->name = $name ?: $sku;
            }

            $group->save();

            return $group;
        };
        $group   = $this->getServiceGroupResolver()->get(
            $oem,
            $sku,
            static function () use ($factory): ServiceGroup {
                return $factory(new ServiceGroup());
            },
        );

        // Update
        if (!$created) {
            $factory($group);
        }

        // Return
        return $group;
    }
}
