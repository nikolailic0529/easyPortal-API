<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Data\Type;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Normalizer\Normalizers\NameNormalizer;
use App\Services\DataLoader\Resolver\Resolvers\TypeResolver;
use App\Utils\Eloquent\Model;

/**
 * @mixin Factory
 */
trait WithType {
    abstract protected function getNormalizer(): Normalizer;

    abstract protected function getTypeResolver(): TypeResolver;

    protected function type(Model $owner, string $type): Type {
        $type = $this->getNormalizer()->string($type);
        $type = $this->getTypeResolver()->get($owner, $type, function () use ($owner, $type): Type {
            $model              = new Type();
            $normalizer         = $this->getNormalizer();
            $model->object_type = $owner->getMorphClass();
            $model->key         = $normalizer->string($type);
            $model->name        = NameNormalizer::normalize($type);

            $model->save();

            return $model;
        });

        return $type;
    }
}
