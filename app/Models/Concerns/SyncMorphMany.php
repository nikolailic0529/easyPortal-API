<?php declare(strict_types = 1);

namespace App\Models\Concerns;

use App\Models\PolymorphicModel;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use InvalidArgumentException;

use function sprintf;

trait SyncMorphMany {
    /**
     * @param \Illuminate\Support\Collection|array<\App\Models\PolymorphicModel> $objects
     */
    protected function syncMorphMany(string $relation, Collection|array $objects): void {
        // TODO [refactor] Probably we need move it into MorphMany class

        // Prepare
        /** @var \Illuminate\Database\Eloquent\Relations\MorphMany $morph */
        $morph = $this->{$relation}();
        $model = $morph->make();
        $class = $model::class;

        if (!($morph instanceof MorphMany)) {
            throw new InvalidArgumentException(sprintf(
                'The `$relation` must be instance of `%s`.',
                MorphMany::class,
            ));
        }

        if (!($model instanceof PolymorphicModel)) {
            throw new InvalidArgumentException(sprintf(
                'Related model should be instance of `%s`.',
                PolymorphicModel::class,
            ));
        }

        // Object should exist
        if (!$this->exists) {
            $this->save();
        }

        // Create/Update existing
        $existing = (clone $this->{$relation})->keyBy(static function (PolymorphicModel $contact): string {
            return $contact->getKey();
        });

        foreach ($objects as $object) {
            // Object supported by relation?
            if (!($object instanceof $class)) {
                throw new InvalidArgumentException(sprintf(
                    'Object should be instance of `%s`.',
                    $class,
                ));
            }

            // We should not update the existing object if it is related to
            // another object type. Probably this is an error.
            if (
                ($object->object_type && $object->object_type !== $this->getMorphClass())
                || ($object->object_id && $object->object_id !== $this->getKey())
            ) {
                throw new InvalidArgumentException(sprintf(
                    'Object related to %s#%s, %s#%s or `null` required.',
                    $object->object_type,
                    $object->object_id,
                    $this->getMorphClass(),
                    $this->getKey(),
                ));
            }

            // Save
            $morph->save($object);

            // Mark as used
            $existing->forget($object->getKey());
        }

        // Delete unused
        foreach ($existing as $object) {
            $this->syncMorphManyDelete($object);
        }

        // Update relation
        $this->setRelation($relation, new Collection($objects));
    }

    protected function syncMorphManyDelete(PolymorphicModel $model): void {
        $model->delete();
    }
}
