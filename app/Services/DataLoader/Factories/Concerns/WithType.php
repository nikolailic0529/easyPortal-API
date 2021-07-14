<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Model;
use App\Models\Type;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\TypeResolver;

/**
 * @mixin \App\Services\DataLoader\Factory
 */
trait WithType {
    abstract protected function getNormalizer(): Normalizer;

    abstract protected function getTypeResolver(): TypeResolver;

    protected function type(Model $owner, string $type): Type {
        $type = $this->getTypeResolver()->get($owner, $type, $this->factory(function () use ($owner, $type): Type {
            $model              = new Type();
            $normalizer         = $this->getNormalizer();
            $model->object_type = $owner->getMorphClass();
            $model->key         = $normalizer->string($type);
            $model->name        = $normalizer->string($type);

            $model->save();

            return $model;
        }));

        return $type;
    }
}
