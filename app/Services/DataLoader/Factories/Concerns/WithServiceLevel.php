<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Oem;
use App\Models\ServiceGroup;
use App\Models\ServiceLevel;
use App\Services\DataLoader\Exceptions\ServiceLevelNotFound;
use App\Services\DataLoader\Finders\ServiceLevelFinder;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\ServiceLevelResolver;

/**
 * @mixin \App\Services\DataLoader\Factory
 */
trait WithServiceLevel {
    abstract protected function getNormalizer(): Normalizer;

    abstract protected function getServiceLevelFinder(): ?ServiceLevelFinder;

    abstract protected function getServiceLevelResolver(): ServiceLevelResolver;

    protected function serviceLevel(Oem $oem, ServiceGroup $group, string $sku): ?ServiceLevel {
        $sku   = $this->getNormalizer()->string($sku);
        $level = $this->getServiceLevelResolver()->get($oem, $group, $sku, $this->factory(
            function () use ($oem, $group, $sku): ?ServiceLevel {
                return $this->getServiceLevelFinder()?->find($oem, $group, $sku);
            },
        ));

        if (!$level) {
            throw new ServiceLevelNotFound($oem, $group, $sku);
        }

        return $level;
    }
}
