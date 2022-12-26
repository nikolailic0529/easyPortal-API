<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Data\Oem;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\OemResolver;

/**
 * @mixin Factory
 */
trait WithOem {
    abstract protected function getNormalizer(): Normalizer;

    abstract protected function getOemResolver(): OemResolver;

    protected function oem(?string $key): ?Oem {
        // Null?
        if ($key === null || $key === '') {
            return null;
        }

        // Find
        return $this->getOemResolver()->get($key, static function () use ($key): Oem {
            $oem       = new Oem();
            $oem->key  = $key;
            $oem->name = $key;

            $oem->save();

            return $oem;
        });
    }
}
