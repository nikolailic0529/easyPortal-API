<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Concerns;

use App\Models\Model;
use App\Utils\Eloquent\Callbacks\GetKey;
use App\Utils\Eloquent\Callbacks\SetKey;
use App\Utils\Eloquent\ModelHelper;
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
        $children = (new EloquentCollection($objects))
            ->map(static function (Model $model): Model {
                return clone $model;
            })
            ->map(new SetKey())
            ->keyBy(new GetKey());
        $objects  = new EloquentCollection();

        foreach ($children as $child) {
            /** @var \Illuminate\Database\Eloquent\Model $child */
            if ($existing->has($child->getKey())) {
                $child = $existing->get($child->getKey());

                $children->forget($child->getKey());
                $existing->forget($child->getKey());
            }

            $objects->push($child);
        }

        // Update relation
        $this->setRelation($relation, $objects);

        // Update database
        if (!$children->isEmpty() || !$existing->isEmpty()) {
            $this->onSave(static function () use ($belongsToMany, $children, $existing): void {
                // Sync
                $model    = $belongsToMany->getPivotClass();
                $parent   = $belongsToMany->getParent()->{$belongsToMany->getParentKeyName()};
                $accessor = $belongsToMany->getPivotAccessor();

                foreach ($children as $object) {
                    $pivot                                             = new $model();
                    $pivot->{$belongsToMany->getRelatedPivotKeyName()} = $object->getKey();
                    $pivot->{$belongsToMany->getForeignPivotKeyName()} = $parent;

                    $pivot->save();

                    $object->setRelation($accessor, $pivot);
                }

                // Delete unused
                /** @var \Illuminate\Database\Eloquent\Model $object */
                foreach ($existing as $object) {
                    $object->{$accessor}?->delete();
                }
            });
        }
    }
}
