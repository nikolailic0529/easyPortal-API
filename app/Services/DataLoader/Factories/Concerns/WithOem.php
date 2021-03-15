<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Oem;

/**
 * @property \App\Services\DataLoader\Normalizer            $normalizer
 * @property \App\Services\DataLoader\Resolvers\OemResolver $oems
 *
 * @mixin \App\Services\DataLoader\Factory
 */
trait WithOem {
    protected function oem(string $abbr, string $name): Oem {
        $oem = $this->oems->get($abbr, $this->factory(function () use ($abbr, $name): Oem {
            $model = new Oem();

            $model->abbr = $this->normalizer->string($abbr);
            $model->name = $this->normalizer->string($name);

            $model->save();

            return $model;
        }));

        return $oem;
    }
}
