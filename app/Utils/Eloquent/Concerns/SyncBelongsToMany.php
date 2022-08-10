<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Concerns;

use App\Utils\Eloquent\Callbacks\GetKey;
use App\Utils\Eloquent\Callbacks\SetKey;
use App\Utils\Eloquent\Model;
use App\Utils\Eloquent\ModelHelper;
use App\Utils\Eloquent\Pivot;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot as EloquentPivot;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Collection as BaseCollection;
use InvalidArgumentException;
use LogicException;

use function array_merge;
use function assert;
use function is_a;
use function sprintf;

/**
 * @mixin Model
 */
trait SyncBelongsToMany {
    use SyncMany;

    /**
     * @param Collection<int, Model>|EloquentCollection<int, Model>|array<Model> $objects
     */
    protected function syncBelongsToMany(string $relation, Collection|array $objects): void {
        // Prepare
        $belongsToMany = $this->getBelongsToMany($relation);
        $existing      = $this->syncManyGetExisting($this, $relation)->keyBy(new GetKey());
        $children      = (new EloquentCollection($objects))
            ->map(static function (Model $model): Model {
                return clone $model;
            })
            ->map(new SetKey())
            ->keyBy(new GetKey());
        $objects       = new EloquentCollection();

        foreach ($children as $child) {
            /** @var Model $child */
            if ($existing->has($child->getKey())) {
                $child = $existing->get($child->getKey());

                assert($child instanceof Model);

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
                $parent   = $belongsToMany->getParent()->getAttribute($belongsToMany->getParentKeyName());
                $accessor = $belongsToMany->getPivotAccessor();

                foreach ($children as $object) {
                    /** @var EloquentPivot $pivot */
                    $pivot = new $model();

                    $pivot->setAttribute($belongsToMany->getRelatedPivotKeyName(), $object->getKey());
                    $pivot->setAttribute($belongsToMany->getForeignPivotKeyName(), $parent);

                    $pivot->save();

                    $object->setRelation($accessor, $pivot);
                }

                // Delete unused
                foreach ($existing as $object) {
                    /** @var EloquentModel $object */
                    $pivot = $object->getAttribute($accessor);

                    if ($pivot instanceof EloquentModel) {
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
     * @param array<string, T>|BaseCollection<string, T> $pivots
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
            /** @var EloquentCollection<array-key, EloquentPivot> $existing */
            $existing = (new ModelHelper($this))->isRelation($relationPivots)
                ? $this->syncManyGetExisting($this, $relationPivots)
                : $wrapper->getCurrentlyAttachedPivots();
            $existing = $existing->keyBy(static function (EloquentPivot $pivot) use ($belongsToMany): string {
                return $pivot->getAttribute($belongsToMany->getRelatedPivotKeyName());
            });
        }

        foreach ($pivots as $key => $pivot) {
            $attributes = $pivot->getDirty();
            $object     = $existing->get($key);

            if ($object instanceof EloquentModel) {
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
