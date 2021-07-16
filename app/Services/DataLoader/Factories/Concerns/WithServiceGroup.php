<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Oem;
use App\Models\ServiceGroup;
use App\Services\DataLoader\Exceptions\ServiceGroupNotFound;
use App\Services\DataLoader\Resolvers\ServiceGroupResolver;

/**
 * @mixin \App\Services\DataLoader\Factory
 */
trait WithServiceGroup {
    abstract protected function getServiceGroupResolver(): ServiceGroupResolver;

    protected function serviceGroup(Oem $oem, string $sku): ?ServiceGroup {
        $group = $this->getServiceGroupResolver()->get($oem, $sku);

        if (!$group) {
            throw new ServiceGroupNotFound($oem, $sku);
        }

        return $group;
    }
}
