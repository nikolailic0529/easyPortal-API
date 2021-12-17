<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Concerns;

use App\Utils\Eloquent\Callbacks\GetUniqueKey;
use App\Utils\Eloquent\Callbacks\SetKey;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use InvalidArgumentException;

use function sprintf;

/**
 * @mixin \App\Utils\Eloquent\Model
 */
trait SyncHasMany {
    use SyncMany;

    /**
     * @param \Illuminate\Support\Collection<\App\Utils\Eloquent\Model>|array<\App\Utils\Eloquent\Model> $objects
     */
    protected function syncHasMany(string $relation, Collection|array $objects): void {
        // Prepare
        /** @var \Illuminate\Database\Eloquent\Relations\HasMany $hasMany */
        $hasMany = $this->{$relation}();

        if (!($hasMany instanceof HasMany)) {
            throw new InvalidArgumentException(sprintf(
                'The `$relation` must be instance of `%s`.',
                HasMany::class,
            ));
        }

        // Prepare
        $keyer    = new GetUniqueKey([$hasMany->getForeignKeyName()]);
        $existing = $this->syncManyGetExisting($this, $relation)->keyBy($keyer);
        $children = (new EloquentCollection($objects))->map(new SetKey())->keyBy($keyer);

        if (!$existing->isEmpty()) {
            foreach ($children as $key => $child) {
                /** @var \Illuminate\Database\Eloquent\Model $child */
                $object = $existing->get($key);

                if ($object instanceof Model) {
                    if ($child->getKey() !== $object->getKey()) {
                        foreach ($child->getDirty() as $attr => $value) {
                            if ($attr !== $child->getKeyName()) {
                                $object->setAttribute($attr, $value);
                            }
                        }

                        $children->put($key, $object);
                    }

                    $existing->forget($key);
                }
            }
        }

        // Update relation
        $this->setRelation($relation, new EloquentCollection($children->values()));

        // Update database
        if (!$children->isEmpty() || !$existing->isEmpty()) {
            $this->onSave(static function () use ($hasMany, $children, $existing): void {
                // Sync
                foreach ($children as $object) {
                    /** @var \App\Utils\Eloquent\Model $object */
                    $hasMany->save($object);
                }

                // Delete unused
                /** @var \App\Utils\Eloquent\Model $object */
                foreach ($existing as $object) {
                    $object->delete();
                }
            });
        }
    }
}