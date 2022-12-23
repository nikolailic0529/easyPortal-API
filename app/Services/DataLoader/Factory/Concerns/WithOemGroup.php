<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Data\Oem;
use App\Models\OemGroup;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\OemGroupResolver;

/**
 * @mixin Factory
 */
trait WithOemGroup {
    abstract protected function getNormalizer(): Normalizer;

    abstract protected function getOemGroupResolver(): OemGroupResolver;

    protected function oemGroup(Oem $oem, string $key, string $name): OemGroup {
        $group = $this->getOemGroupResolver()
            ->get($oem, $key, $name, function () use ($oem, $key, $name): OemGroup {
                $model       = new OemGroup();
                $normalizer  = $this->getNormalizer();
                $model->oem  = $oem;
                $model->key  = $normalizer->string($key);
                $model->name = $normalizer->string($name);

                $model->save();

                return $model;
            });

        return $group;
    }
}
