<?php declare(strict_types = 1);

namespace App\Models\Concerns;

use App\Models\Callbacks\GetKey;
use App\Models\Callbacks\SetKey;
use App\Utils\ModelHelper;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use InvalidArgumentException;

use function sprintf;

/**
 * @mixin \App\Models\Model
 */
trait SyncBelongsToMany {
    use SyncMany;

    /**
     * @param \Illuminate\Support\Collection<\App\Models\Model>|array<\App\Models\Model> $objects
     */
    protected function syncBelongsToMany(string $relation, Collection|array $objects): void {
        // Prepare
        /** @var \Illuminate\Database\Eloquent\Relations\BelongsToMany $belongsToMany */
        $belongsToMany = (new ModelHelper($this))->getRelation($relation);

        if (!($belongsToMany instanceof BelongsToMany)) {
            throw new InvalidArgumentException(sprintf(
                'The `$relation` must be instance of `%s`.',
                BelongsToMany::class,
            ));
        }

        // Prepare
        $existing = $this->syncManyGetExisting($this, $relation)->keyBy(new GetKey());
        $children = (new EloquentCollection($objects))->map(new SetKey())->keyBy(new GetKey());

        if (!$existing->isEmpty()) {
            foreach ($children as $child) {
                /** @var \Illuminate\Database\Eloquent\Model $child */
                if ($existing->has($child->getKey())) {
                    $children->forget($child->getKey());
                    $existing->forget($child->getKey());
                }
            }
        }

        // Update relation
        $this->setRelation($relation, new EloquentCollection($objects));

        // Update database
        if (!$children->isEmpty() || !$existing->isEmpty()) {
            $this->onSave(static function () use ($belongsToMany, $children, $existing): void {
                // Sync
                foreach ($children as $object) {
                    /** @var \Illuminate\Database\Eloquent\Model $object */
                    $belongsToMany->attach($object->getKey(), [], false);
                }

                // Delete unused
                /** @var \Illuminate\Database\Eloquent\Model $object */
                foreach ($existing as $object) {
                    $object->pivot?->delete();
                }
            });
        }
    }
}
