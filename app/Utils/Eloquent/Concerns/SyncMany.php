<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

use function assert;

trait SyncMany {
    /**
     * @return Collection<array-key, Model>
     */
    protected function syncManyGetExisting(Model $model, string $relation): Collection {
        // Required because Laravel will try to load relation if the attribute
        // has a value. In our case, it will cause additional queries for
        // models created by DataLoader while import.
        if (!$model->exists && !$model->relationLoaded($relation)) {
            $model->setRelation($relation, new Collection());
        }

        $existing = $model->getAttribute($relation);

        assert($existing instanceof Collection);

        return $existing;
    }
}
