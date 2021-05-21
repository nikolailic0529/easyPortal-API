<?php declare(strict_types = 1);

namespace App\Models\Concerns;

use App\Models\Model;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use InvalidArgumentException;

use function sprintf;

/**
 * @mixin \App\Models\Model
 */
trait SyncHasMany {
    /**
     * @param \Illuminate\Support\Collection<\App\Models\Model>|array<\App\Models\Model> $objects
     */
    protected function syncHasMany(string $relation, Collection|array $objects): void {
        // Prepare
        /** @var \Illuminate\Database\Eloquent\Relations\HasMany $hasMany */
        $hasMany = $this->{$relation}();

        if (!($hasMany instanceof HasMany)) {
            throw new InvalidArgumentException(sprintf(
                'The `$relation` must be instance of `%s`.',
                HasMany::class,
            ));
        }

        // Object should exist
        $getKey   = static function (Model $model): string {
            return $model->getKey();
        };
        $new      = new EloquentCollection($objects);
        $existing = new Collection();

        if ($this->exists) {
            $existing = $this->{$relation}->keyBy($getKey);
        } else {
            $this->save();
        }

        // Add new
        /** @var \Illuminate\Database\Eloquent\Model $object */
        foreach ($new as $object) {
            // Attach if not attached
            if (!$existing->has($object->getKey())) {
                $hasMany->save($object);
            }

            // Mark as used
            $existing->forget($object->getKey());
        }

        // Remove unused
        /** @var \Illuminate\Database\Eloquent\Model $object */
        foreach ($existing as $object) {
            $object->delete();
        }

        // Update relation
        $this->setRelation($relation, $new->values());
    }
}
