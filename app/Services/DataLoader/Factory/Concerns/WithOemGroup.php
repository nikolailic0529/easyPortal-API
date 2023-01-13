<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Data\Oem;
use App\Models\OemGroup;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Normalizers\NameNormalizer;
use App\Services\DataLoader\Resolver\Resolvers\OemGroupResolver;

/**
 * @mixin Factory
 */
trait WithOemGroup {
    abstract protected function getOemGroupResolver(): OemGroupResolver;

    protected function oemGroup(Oem $oem, string $key, string $name): OemGroup {
        $group = $this->getOemGroupResolver()
            ->get($oem, $key, $name, static function (?OemGroup $model) use ($oem, $key, $name): OemGroup {
                if ($model) {
                    return $model;
                }

                $model       = new OemGroup();
                $model->oem  = $oem;
                $model->key  = $key;
                $model->name = NameNormalizer::normalize($name);

                $model->save();

                return $model;
            });

        return $group;
    }
}
