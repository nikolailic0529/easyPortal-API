<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Callbacks;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Str;

use function is_null;

class SetKey {
    /**
     * @template T of \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Relations\Pivot
     *
     * @param T $model
     *
     * @return T
     */
    public function __invoke(Model|Pivot $model): Model|Pivot {
        if (!$model->exists && is_null($model->getAttribute($model->getKeyName()))) {
            $model->setAttribute($model->getKeyName(), Str::uuid()->toString());
        }

        return $model;
    }
}
