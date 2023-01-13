<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Data\Oem;
use App\Models\Data\ServiceGroup;
use App\Models\Data\ServiceLevel;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Resolver\Resolvers\ServiceLevelResolver;

/**
 * @mixin Factory
 */
trait WithServiceLevel {
    abstract protected function getServiceLevelResolver(): ServiceLevelResolver;

    protected function serviceLevel(
        Oem $oem,
        ServiceGroup $group,
        string $sku,
        string $name = null,
        string $description = null,
    ): ?ServiceLevel {
        // Empty?
        if ($sku === '') {
            return null;
        }

        // Find/Create
        return $this->getServiceLevelResolver()->get(
            $oem,
            $group,
            $sku,
            static function (?ServiceLevel $level) use ($oem, $group, $sku, $name, $description): ServiceLevel {
                $level ??= new ServiceLevel();

                if (!$level->exists) {
                    $level->key          = "{$group->getTranslatableKey()}/{$sku}";
                    $level->oem          = $oem;
                    $level->sku          = $sku;
                    $level->description  = '';
                    $level->serviceGroup = $group;
                }

                if (!$level->name || $level->name === $sku) {
                    $level->name = $name ?: $sku;
                }

                if (!$level->description) {
                    $level->description = (string) $description;
                }

                $level->save();

                return $level;
            },
        );
    }
}
