<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Oem;
use App\Services\DataLoader\Exceptions\OemNotFound;
use App\Services\DataLoader\Finders\OemFinder;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\OemResolver;

/**
 * @mixin \App\Services\DataLoader\Factory\Factory
 */
trait WithOem {
    abstract protected function getNormalizer(): Normalizer;

    abstract protected function getOemFinder(): ?OemFinder;

    abstract protected function getOemResolver(): OemResolver;

    protected function oem(string $key): Oem {
        $key = $this->getNormalizer()->string($key);
        $oem = $this->getOemResolver()->get($key, $this->factory(
            function () use ($key): ?Oem {
                return $this->getOemFinder()?->find($key);
            },
        ));

        if (!$oem) {
            throw new OemNotFound($key);
        }

        return $oem;
    }
}