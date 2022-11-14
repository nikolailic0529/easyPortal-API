<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Concerns;

use App\Utils\Eloquent\Callbacks\GetKey;
use App\Utils\Eloquent\ModelHelper;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use LogicException;

use function array_merge;
use function is_a;
use function is_int;
use function is_string;
use function sprintf;

/**
 * @mixin Model
 */
trait SyncBelongsToMany {
    use SyncMany;

    /**
     * @template T of Model
     *
     * @param Collection<int, T> $objects
     */
    protected function syncBelongsToMany(string $relation, Collection $objects): void {
        // Prepare
        $belongsToMany = $this->getBelongsToMany($relation);
        $existing      = $this->syncManyGetExisting($this, $relation)->keyBy(new GetKey());
        $children      = $objects->all();
        $added         = [];

        foreach ($children as $child) {
            $key = $child->getKey();

            if ((is_string($key) || is_int($key)) && $existing->has($key)) {
                $existing->forget($key);
            } else {
                $added[] = $child;
            }
        }

        // Update relation
        $this->setRelation($relation, EloquentCollection::make($children));

        // Update database
        if (!!$children || !!$added || !$existing->isEmpty()) {
            $this->onSave(static function () use ($belongsToMany, $children, $added, $existing): void {
                // Sync
                foreach ($children as $child) {
                    $child->save();
                }

                // Pivots
                $model    = $belongsToMany->getPivotClass();
                $parent   = $belongsToMany->getParent()->getAttribute($belongsToMany->getParentKeyName());
                $accessor = $belongsToMany->getPivotAccessor();

                foreach ($added as $object) {
                    /** @var Pivot $pivot */
                    $pivot = new $model();

                    $pivot->setAttribute($belongsToMany->getRelatedPivotKeyName(), $object->getKey());
                    $pivot->setAttribute($belongsToMany->getForeignPivotKeyName(), $parent);

                    $pivot->save();

                    $object->setRelation($accessor, $pivot);
                }

                // Delete unused
                foreach ($existing as $object) {
                    /** @var Model $object */
                    $pivot = $object->getAttribute($accessor);

                    if ($pivot instanceof Model) {
                        $pivot->delete();
                    }
                }
            });
        }
    }

    /**
     * The main problem with `sync()` is that it is using object keys instead of
     * pivot keys and if any conditions are added to the relationship the custom
     * class will not be used. The second problem: if the custom class is used
     * the pivots will be loaded twice.
     *
     * @see \Illuminate\Database\Eloquent\Relations\BelongsToMany::sync()
     * @see \App\Utils\Eloquent\SmartSave\Upsertable
     *
     * @template T of Pivot
     *
     * @param array<string, T>|Collection<string, T> $pivots
     */
    protected function syncBelongsToManyPivots(string $relation, Collection|array $pivots): void {
        // Prepare
        $belongsToMany = $this->getBelongsToMany($relation);

        if (!is_a($belongsToMany->getPivotClass(), Pivot::class, true)) {
            throw new LogicException(sprintf(
                'Pivot must be instance of `%s`.',
                Pivot::class,
            ));
        }

        // Process
        $wrapper        = new SyncBelongsToManyWrapper($belongsToMany);
        $children       = new Collection();
        $existing       = new EloquentCollection();
        $relationPivots = "{$relation}Pivots";

        if ($this->exists) {
            /** @var EloquentCollection<array-key, Pivot> $existing */
            $existing = (new ModelHelper($this))->isRelation($relationPivots)
                ? $this->syncManyGetExisting($this, $relationPivots)
                : $wrapper->getCurrentlyAttachedPivots();
            $existing = $existing->keyBy(static function (Pivot $pivot) use ($belongsToMany): string {
                return $pivot->getAttribute($belongsToMany->getRelatedPivotKeyName());
            });
        }

        foreach ($pivots as $key => $pivot) {
            $attributes = $pivot->getDirty();
            $object     = $existing->get($key);

            if ($object instanceof Model) {
                $attributes = array_merge($attributes, $wrapper->getPivotAttributes($key));
                $attributes = Arr::except($attributes, [
                    $object->getKeyName(),
                    $object->getCreatedAtColumn(),
                    $object->getUpdatedAtColumn(),
                ]);

                $children->push($object->forceFill($attributes));
                $existing->forget($key);
            } else {
                $children->push($wrapper->createNewPivot($key, $attributes));
            }
        }

        // Update relation
        $this->unsetRelation($relation);
        $this->unsetRelation($relationPivots);

        // Update database
        if (!$children->isEmpty() || !$existing->isEmpty()) {
            $this->onSave(static function () use ($belongsToMany, $children, $existing): void {
                // Sync
                $key    = $belongsToMany->getForeignPivotKeyName();
                $parent = $belongsToMany->getParent()->getAttribute($belongsToMany->getParentKeyName());

                foreach ($children as $pivot) {
                    /** @var Pivot $pivot */
                    $pivot->setAttribute($key, $parent);
                    $pivot->save();
                }

                // Delete unused
                foreach ($existing as $pivot) {
                    /** @var Pivot $pivot */
                    $pivot->delete();
                }
            });
        }
    }

    /**
     * @return BelongsToMany<static>
     */
    private function getBelongsToMany(string $relation): BelongsToMany {
        /** @var BelongsToMany<static>|mixed $belongsToMany */
        $belongsToMany = (new ModelHelper($this))->getRelation($relation);

        if (!($belongsToMany instanceof BelongsToMany)) {
            throw new InvalidArgumentException(sprintf(
                'The `$relation` must be instance of `%s`.',
                BelongsToMany::class,
            ));
        }

        return $belongsToMany;
    }
}
