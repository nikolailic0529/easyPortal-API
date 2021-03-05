<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Model;
use App\Models\Status;

/**
 * @property \App\Services\DataLoader\Normalizer               $normalizer
 * @property \App\Services\DataLoader\Providers\StatusProvider $statuses
 */
trait WithStatus {
    protected function status(Model $owner, string $status): Status {
        $status = $this->statuses->get($owner, $status, function () use ($owner, $status): Status {
            $model = new Status();

            $model->object_type = $owner->getMorphClass();
            $model->key         = $this->normalizer->string($status);
            $model->name        = $this->normalizer->string($status);

            $model->save();

            return $model;
        });

        return $status;
    }
}
