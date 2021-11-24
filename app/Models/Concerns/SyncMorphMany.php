<?php declare(strict_types = 1);

namespace App\Models\Concerns;

use App\Models\Callbacks\GetKey;
use App\Models\Callbacks\SetKey;
use App\Models\PolymorphicModel;
use App\Utils\Eloquent\ModelHelper;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use InvalidArgumentException;

use function sprintf;

trait SyncMorphMany {
    use SyncMany;

    /**
     * @param \Illuminate\Support\Collection|array<\App\Models\PolymorphicModel> $objects
     */
    protected function syncMorphMany(string $relation, Collection|array $objects): void {
        // TODO [refactor] Probably we need move it into MorphMany class

        // Prepare
        /** @var \Illuminate\Database\Eloquent\Relations\MorphMany $morph */
        $morph = (new ModelHelper($this))->getRelation($relation);
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

        // Create/Update existing
        $existing = $this->syncManyGetExisting($this, $relation)->map(new SetKey())->keyBy(new GetKey());
        $children = new EloquentCollection($objects);

        foreach ($children as $object) {
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

            // Mark as used
            $existing->forget($object->getKey());
        }

        // Update relation
        $this->setRelation($relation, new EloquentCollection($objects));

        // Update database
        if (!$children->isEmpty() || !$existing->isEmpty()) {
            $this->onSave(function () use ($morph, $children, $existing): void {
                // Sync
                foreach ($children as $object) {
                    $morph->save($object);
                }

                // Delete unused
                foreach ($existing as $object) {
                    $this->syncMorphManyDelete($object);
                }
            });
        }
    }

    protected function syncMorphManyDelete(PolymorphicModel $model): void {
        $model->delete();
    }
}
