<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Oem;
use App\Models\OemGroup;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\OemGroupResolver;

/**
 * @mixin \App\Services\DataLoader\Factory
 */
trait WithOemGroup {
    abstract protected function getNormalizer(): Normalizer;

    abstract protected function getOemGroupResolver(): OemGroupResolver;

    protected function oemGroup(Oem $oem, string $key, string $name): OemGroup {
        $group = $this->getOemGroupResolver()->get($oem, $key, $name, $this->factory(
            function () use ($oem, $key, $name): OemGroup {
                $model       = new OemGroup();
                $normalizer  = $this->getNormalizer();
                $model->oem  = $oem;
                $model->key  = $normalizer->string($key);
                $model->name = $normalizer->string($name);

                $model->save();

                return $model;
            },
        ));

        return $group;
    }
}
