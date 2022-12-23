<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Data\Oem;
use App\Models\Data\ServiceGroup;
use App\Models\Data\ServiceLevel;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\ServiceLevelResolver;

/**
 * @mixin Factory
 */
trait WithServiceLevel {
    abstract protected function getNormalizer(): Normalizer;

    abstract protected function getServiceLevelResolver(): ServiceLevelResolver;

    protected function serviceLevel(
        Oem $oem,
        ServiceGroup $group,
        string $sku,
        string $name = null,
        string $description = null,
    ): ?ServiceLevel {
        // Null?
        $sku = $this->getNormalizer()->string($sku) ?: null;

        if ($sku === null) {
            return null;
        }

        // Find/Create
        $created = false;
        $factory = function (
            ServiceLevel $level,
        ) use (
            &$created,
            $oem,
            $group,
            $sku,
            $name,
            $description,
        ): ServiceLevel {
            $created    = !$level->exists;
            $normalizer = $this->getNormalizer();

            if ($created) {
                $level->key          = "{$group->getTranslatableKey()}/{$sku}";
                $level->oem          = $oem;
                $level->sku          = $sku;
                $level->description  = '';
                $level->serviceGroup = $group;
            }

            if (!$level->name || $level->name === $sku) {
                $level->name = $normalizer->string($name) ?: $sku;
            }

            if (!$level->description) {
                $level->description = (string) $description;
            }

            $level->save();

            return $level;
        };
        $level   = $this->getServiceLevelResolver()->get(
            $oem,
            $group,
            $sku,
            static function () use ($factory): ServiceLevel {
                return $factory(new ServiceLevel());
            },
        );

        // Update
        if (!$created) {
            $factory($level);
        }

        // Return
        return $level;
    }
}
