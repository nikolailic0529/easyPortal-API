<?php declare(strict_types = 1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

trait SyncMany {
    protected function syncManyGetExisting(Model $model, string $relation): Collection {
        // Required because Laravel will try to load relation if the attribute
        // has a value. In our case, it will cause additional queries for
        // models created by DataLoader while import.
        return $model->exists || $model->relationLoaded($relation)
            ? $model->getAttribute($relation)
            : new Collection();
    }
}
