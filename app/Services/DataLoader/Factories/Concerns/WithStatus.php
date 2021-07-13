<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Model;
use App\Models\Status;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\StatusResolver;

/**
 * @mixin \App\Services\DataLoader\Factory
 */
trait WithStatus {
    abstract protected function getNormalizer(): Normalizer;

    abstract protected function getStatusResolver(): StatusResolver;

    protected function status(Model $owner, string $status): Status {
        $status = $this->getStatusResolver()->get($owner, $status, $this->factory(
            function () use ($owner, $status): Status {
                $model              = new Status();
                $normalizer         = $this->getNormalizer();
                $model->object_type = $owner->getMorphClass();
                $model->key         = $normalizer->string($status);
                $model->name        = $normalizer->string($status);

                $model->save();

                return $model;
            },
        ));

        return $status;
    }
}
