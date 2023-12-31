<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Data\Status;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Normalizers\NameNormalizer;
use App\Services\DataLoader\Resolver\Resolvers\StatusResolver;
use App\Utils\Eloquent\Model;

/**
 * @mixin Factory
 */
trait WithStatus {
    abstract protected function getStatusResolver(): StatusResolver;

    protected function status(Model $owner, string $status): Status {
        return $this->getStatusResolver()
            ->get($owner, $status, static function (?Status $model) use ($owner, $status): Status {
                if ($model) {
                    return $model;
                }

                $model              = new Status();
                $model->object_type = $owner->getMorphClass();
                $model->key         = $status;
                $model->name        = NameNormalizer::normalize($status);

                $model->save();

                return $model;
            });
    }
}
