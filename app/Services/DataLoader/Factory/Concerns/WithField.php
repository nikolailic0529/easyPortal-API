<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Field;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\FieldResolver;
use App\Utils\Eloquent\Model;

/**
 * @mixin Factory
 */
trait WithField {
    abstract protected function getNormalizer(): Normalizer;

    abstract protected function getFieldResolver(): FieldResolver;

    protected function field(Model $owner, string $field): Field {
        $field = $this->getNormalizer()->string($field);
        $field = $this->getFieldResolver()->get($owner, $field, $this->factory(function () use ($owner, $field): Field {
            $model              = new Field();
            $normalizer         = $this->getNormalizer();
            $model->object_type = $owner->getMorphClass();
            $model->key         = $normalizer->string($field);
            $model->name        = $normalizer->name($field);

            $model->save();

            return $model;
        }));

        return $field;
    }
}
