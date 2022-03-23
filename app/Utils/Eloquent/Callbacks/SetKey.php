<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Callbacks;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Str;
use LogicException;

class SetKey {
    /**
     * @template T of Model|Pivot
     *
     * @param T $model
     *
     * @return T
     */
    public function __invoke(Model|Pivot $model): Model|Pivot {
        if ($model->getKeyType() !== 'string') {
            throw new LogicException('Models with numeric keys are not supported.');
        }

        if (!$model->exists && $model->getAttribute($model->getKeyName()) === null) {
            $model->setAttribute($model->getKeyName(), Str::uuid()->toString());
        }

        return $model;
    }
}
