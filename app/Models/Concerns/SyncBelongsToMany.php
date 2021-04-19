<?php declare(strict_types = 1);

namespace App\Models\Concerns;

use App\Models\Model;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use InvalidArgumentException;

use function sprintf;

/**
 * @mixin \App\Models\Model
 */
trait SyncBelongsToMany {
    /**
     * @param \Illuminate\Support\Collection<\App\Models\Model>|array<\App\Models\Model> $objects
     */
    protected function syncBelongsToMany(string $relation, Collection|array $objects): void {
        // Prepare
        /** @var \Illuminate\Database\Eloquent\Relations\BelongsToMany $belongsToMany */
        $belongsToMany = $this->{$relation}();

        if (!($belongsToMany instanceof BelongsToMany)) {
            throw new InvalidArgumentException(sprintf(
                'The `$relation` must be instance of `%s`.',
                BelongsToMany::class,
            ));
        }

        // Object should exist
        $getKey   = static function (Model $model): string {
            return $model->getKey();
        };
        $new      = (new EloquentCollection($objects))->keyBy($getKey);
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
                $belongsToMany->attach($object->getKey(), [], false);
            }

            // Mark as used
            $existing->forget($object->getKey());
        }

        // Remove unused
        /** @var \Illuminate\Database\Eloquent\Model $object */
        foreach ($existing as $object) {
            $object->pivot?->delete();
        }

        // Update relation
        $this->setRelation($relation, $new->values());
    }
}
