<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Oem;
use App\Services\DataLoader\Exceptions\OemNotFound;
use App\Services\DataLoader\Finders\OemFinder;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\OemResolver;

/**
 * @mixin \App\Services\DataLoader\Factory
 */
trait WithOem {
    abstract protected function getNormalizer(): Normalizer;

    abstract protected function getOemFinder(): ?OemFinder;

    abstract protected function getOemResolver(): OemResolver;

    protected function oem(string $abbr): Oem {
        $abbr = $this->getNormalizer()->string($abbr);
        $oem  = $this->getOemResolver()->get($abbr, $this->factory(
            function () use ($abbr): ?Oem {
                return $this->getOemFinder()?->find($abbr);
            },
        ));

        if (!$oem) {
            throw new OemNotFound($abbr);
        }

        return $oem;
    }
}
