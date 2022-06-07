<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Oem;
use App\Models\ServiceGroup;
use App\Models\ServiceLevel;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\ServiceLevelResolver;

/**
 * @mixin Factory
 */
trait WithServiceLevel {
    abstract protected function getNormalizer(): Normalizer;

    abstract protected function getServiceLevelResolver(): ServiceLevelResolver;

    protected function serviceLevel(Oem $oem, ServiceGroup $group, string $sku): ?ServiceLevel {
        // Null?
        $sku = $this->getNormalizer()->string($sku) ?: null;

        if ($sku === null) {
            return null;
        }

        // Find/Create
        return $this->getServiceLevelResolver()->get($oem, $group, $sku, $this->factory(
            static function () use ($oem, $group, $sku): ServiceLevel {
                $level               = new ServiceLevel();
                $level->key          = "{$group->getTranslatableKey()}/{$sku}";
                $level->oem          = $oem;
                $level->sku          = $sku;
                $level->name         = $sku;
                $level->description  = '';
                $level->serviceGroup = $group;

                $level->save();

                return $level;
            },
        ));
    }
}
