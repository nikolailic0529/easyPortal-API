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
use InvalidArgumentException;
use LogicException;

use function array_merge;
use function is_a;
use function reset;
use function sprintf;

/**
 * @mixin Model
 */
trait SyncBelongsToMany {
    use SyncMany;

    /**
     * @param Collection<int, Model>|array<Model> $objects
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
            /** @var EloquentModel $child */
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
     * @param array<string, Collection<string, Pivot>> $pivots
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
        $wrapper        = new class($belongsToMany) extends BelongsToMany {
            public function __construct(
                protected BelongsToMany $belongsToMany,
            ) {
                parent::__construct(
                    $this->belongsToMany->getQuery(),
                    $this->belongsToMany->getParent(),
                    $this->belongsToMany->getTable(),
                    $this->belongsToMany->getForeignPivotKeyName(),
                    $this->belongsToMany->getRelatedPivotKeyName(),
                    $this->belongsToMany->getParentKeyName(),
                    $this->belongsToMany->getRelatedKeyName(),
                    $this->belongsToMany->getRelationName(),
                );
            }

            /**
             * @return Collection<int, Pivot>
             */
            public function getCurrentlyAttachedPivots(): Collection {
                return $this->belongsToMany->getCurrentlyAttachedPivots();
            }

            /**
             * @param array<string,mixed> $attributes
             */
            public function createNewPivot(string $key, array $attributes): EloquentPivot {
                $records = $this->belongsToMany->formatAttachRecords(
                    $this->belongsToMany->parseIds($key),
                    $attributes,
                );
                $pivot   = $this->belongsToMany->newPivot(reset($records), false);

                return $pivot;
            }

            /**
             * @return array<string, mixed>
             */
            public function getPivotAttributes(string $key): array {
                return $this->belongsToMany->formatAttachRecord(0, $key, [], false);
            }
        };
        $children       = new Collection();
        $existing       = new EloquentCollection();
        $relationPivots = "{$relation}Pivots";

        if ($this->exists) {
            $existing = (new ModelHelper($this))->isRelation($relationPivots)
                ? $this->syncManyGetExisting($this, $relationPivots)
                : $wrapper->getCurrentlyAttachedPivots();
            $existing = $existing->keyBy(static function (Pivot $pivot) use ($belongsToMany): string {
                return $pivot->getAttribute($belongsToMany->getRelatedPivotKeyName());
            });
        }

        foreach ($pivots as $key => $pivot) {
            $attributes = $pivot->getDirty();

            if ($existing->has($key)) {
                $object     = $existing->get($key);
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

    private function getBelongsToMany(string $relation): BelongsToMany {
        /** @var BelongsToMany $belongsToMany */
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
