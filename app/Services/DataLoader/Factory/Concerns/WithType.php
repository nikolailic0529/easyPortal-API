<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Type;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\TypeResolver;
use App\Utils\Eloquent\Model;

/**
 * @mixin \App\Services\DataLoader\Factory\Factory
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
