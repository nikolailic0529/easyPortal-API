<?php declare(strict_types = 1);

namespace App\Models\Concerns;

use App\Models\Model;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * @mixin \App\Models\Model
 */
trait SyncBelongsToMany {
    /**
     * @param \Illuminate\Support\Collection<\App\Models\Model>|array<\App\Models\Model> $objects
     */
    protected function syncBelongsToMany(string $relation, Collection|array $objects): void {
        // Object should exist
        if (!$this->exists) {
            $this->save();
        }

        // Sync
        $objects = new EloquentCollection($objects);

        $this->setRelation($relation, $objects);
        $this->{$relation}()->sync($objects->map(static function (Model $model): string {
            return $model->getKey();
        })->all());
    }
}
