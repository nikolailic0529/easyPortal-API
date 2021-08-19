<?php declare(strict_types = 1);

namespace App\Models\Callbacks;

use App\Models\Model;
use App\Models\Pivot;
use Illuminate\Support\Str;

use function is_null;

class SetKey {
    /**
     * @template T of \App\Models\Model|\App\Models\Pivot
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
