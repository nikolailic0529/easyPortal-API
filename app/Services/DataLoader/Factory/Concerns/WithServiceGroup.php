<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Oem;
use App\Models\ServiceGroup;
use App\Services\DataLoader\Exceptions\ServiceGroupNotFound;
use App\Services\DataLoader\Finders\ServiceGroupFinder;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\ServiceGroupResolver;

/**
 * @mixin \App\Services\DataLoader\Factory\Factory
 */
trait WithServiceGroup {
    abstract protected function getNormalizer(): Normalizer;

    abstract protected function getServiceGroupFinder(): ?ServiceGroupFinder;

    abstract protected function getServiceGroupResolver(): ServiceGroupResolver;

    protected function serviceGroup(Oem $oem, string $sku): ?ServiceGroup {
        $sku   = $this->getNormalizer()->string($sku);
        $group = $this->getServiceGroupResolver()->get($oem, $sku, $this->factory(
            function () use ($oem, $sku): ?ServiceGroup {
                return $this->getServiceGroupFinder()?->find($oem, $sku);
            },
        ));

        if (!$group) {
            throw new ServiceGroupNotFound($oem, $sku);
        }

        return $group;
    }
}