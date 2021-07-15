<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Oem;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\OemResolver;

/**
 * @mixin \App\Services\DataLoader\Factory
 */
trait WithOem {
    abstract protected function getNormalizer(): Normalizer;

    abstract protected function getOemResolver(): OemResolver;

    protected function oem(string $abbr, string $name): Oem {
        $oem = $this->getOemResolver()->get($abbr, $this->factory(function () use ($abbr, $name): Oem {
            $model       = new Oem();
            $normalizer  = $this->getNormalizer();
            $model->abbr = $normalizer->string($abbr);
            $model->name = $normalizer->string($name);

            $model->save();

            return $model;
        }));

        return $oem;
    }
}
