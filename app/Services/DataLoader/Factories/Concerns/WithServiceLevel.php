<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Oem;
use App\Models\ServiceGroup;
use App\Models\ServiceLevel;
use App\Services\DataLoader\Exceptions\ServiceLevelNotFound;
use App\Services\DataLoader\Resolvers\ServiceLevelResolver;

/**
 * @mixin \App\Services\DataLoader\Factory
 */
trait WithServiceLevel {
    abstract protected function getServiceLevelResolver(): ServiceLevelResolver;

    protected function serviceLevel(Oem $oem, ServiceGroup $group, string $sku): ?ServiceLevel {
        $level = $this->getServiceLevelResolver()->get($oem, $group, $sku);

        if (!$level) {
            throw new ServiceLevelNotFound($oem, $group, $sku);
        }

        return $level;
    }
}
