<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Concerns;

use App\Utils\Eloquent\Callbacks\GetKey;
use App\Utils\Eloquent\Callbacks\SetKey;
use App\Utils\Eloquent\Model;
use App\Utils\Eloquent\ModelHelper;
use App\Utils\Eloquent\Pivot;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot as EloquentPivot;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use LogicException;

use function is_a;
use function reset;
use function sprintf;

/**
 * @mixin \App\Utils\Eloquent\Model
 */
trait SyncBelongsToMany {
    use SyncMany;

    /**
     * @param \Illuminate\Support\Collection<\App\Utils\Eloquent\Model>|array<\App\Utils\Eloquent\Model> $objects
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

    /**
     * The main problem with `sync()` is that it is using object keys instead of
     * pivot keys and if any conditions are added to the relationship the custom
     * class will not be used. The second problem: if the custom class is used
     * the pivots will be loaded twice.
     *
     * @see \Illuminate\Database\Eloquent\Relations\BelongsToMany::sync()
     * @see \App\Utils\Eloquent\SmartSave\Upsertable
     *
     * @param array<string,array<string,mixed>> $pivots
     */
    protected function syncBelongsToManyPivots(string $relation, array $pivots): void {
        // Prepare
        $belongsToMany = $this->getBelongsToMany($relation);

        if (!is_a($belongsToMany->getPivotClass(), Pivot::class, true)) {
            throw new LogicException(sprintf(
                'Pivot must be instance of `%s`.',
                Pivot::class,
            ));
        }

        // Process
        $wrapper  = new class($belongsToMany) extends BelongsToMany {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected BelongsToMany $belongsToMany,
            ) {
                // empty
            }

            /**
             * @return \Illuminate\Support\Collection<\App\Utils\Eloquent\Pivot>
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
        };
        $children = new Collection();
        $existing = new EloquentCollection();

        if ($this->exists) {
            $existing = $wrapper
                ->getCurrentlyAttachedPivots()
                ->keyBy(static function (Pivot $pivot) use ($belongsToMany): string {
                    return $pivot->{$belongsToMany->getRelatedPivotKeyName()};
                });
        }

        foreach ($pivots as $key => $attributes) {
            if ($existing->has($key)) {
                $children->push($existing->get($key)->forceFill($attributes));
                $existing->forget($key);
            } else {
                $children->push($wrapper->createNewPivot($key, $attributes));
            }
        }

        // Update relation
        $this->unsetRelation($relation);

        // Update database
        if (!$children->isEmpty() || !$existing->isEmpty()) {
            $this->onSave(static function () use ($belongsToMany, $children, $existing): void {
                // Sync
                $key    = $belongsToMany->getForeignPivotKeyName();
                $parent = $belongsToMany->getParent()->{$belongsToMany->getParentKeyName()};

                foreach ($children as $pivot) {
                    /** @var \App\Utils\Eloquent\Pivot $pivot */
                    $pivot->{$key} = $parent;
                    $pivot->save();
                }

                // Delete unused
                foreach ($existing as $pivot) {
                    /** @var \App\Utils\Eloquent\Pivot $pivot */
                    $pivot->delete();
                }
            });
        }
    }

    private function getBelongsToMany(string $relation): BelongsToMany {
        /** @var \Illuminate\Database\Eloquent\Relations\BelongsToMany $belongsToMany */
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
