<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Model;
use App\Models\Type;

/**
 * @property \App\Services\DataLoader\Normalizer             $normalizer
 * @property \App\Services\DataLoader\Providers\TypeProvider $types
 *
 * @mixin \App\Services\DataLoader\Factory
 */
trait WithType {
    protected function type(Model $owner, string $type): Type {
        $type = $this->types->get($owner, $type, $this->factory(function () use ($owner, $type): Type {
            $model = new Type();

            $model->object_type = $owner->getMorphClass();
            $model->key         = $this->normalizer->string($type);
            $model->name        = $this->normalizer->string($type);

            $model->save();

            return $model;
        }));

        return $type;
    }
}
