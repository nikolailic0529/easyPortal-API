<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Concerns;

use App\Utils\Eloquent\Callbacks\GetKey;
use App\Utils\Eloquent\Callbacks\SetKey;
use App\Utils\Eloquent\ModelHelper;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use InvalidArgumentException;

use function sprintf;

trait SyncMorphMany {
    use SyncMany;

    /**
     * @template T of \App\Models\Contact|\App\Models\File
     *
     * @param Collection<array-key, T> $objects
     */
    protected function syncMorphMany(string $relation, Collection $objects): void {
        // todo(Utils/Eloquent): Probably we need move it into MorphMany class

        // Prepare
        $morph = (new ModelHelper($this))->getRelation($relation);

        if (!($morph instanceof MorphMany)) {
            throw new InvalidArgumentException(sprintf(
                'The `$relation` must be instance of `%s`.',
                MorphMany::class,
            ));
        }

        // Create/Update existing
        $model    = $morph->make();
        $class    = $model::class;
        $existing = $this->syncManyGetExisting($this, $relation)->map(new SetKey())->keyBy(new GetKey());
        $children = $objects->all();

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
            if ($object->hasKey()) {
                $existing->forget($object->getKey());
            }
        }

        // Update relation
        $this->setRelation($relation, EloquentCollection::make($children));

        // Update database
        if (!!$children || !$existing->isEmpty()) {
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

    protected function syncMorphManyDelete(Model $model): void {
        $model->delete();
    }
}
