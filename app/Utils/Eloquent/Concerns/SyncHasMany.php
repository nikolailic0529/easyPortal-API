<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Concerns;

use App\Utils\Eloquent\Callbacks\GetKey;
use App\Utils\Eloquent\Callbacks\SetKey;
use App\Utils\Eloquent\ModelHelper;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use InvalidArgumentException;

use function assert;
use function is_int;
use function is_string;
use function sprintf;

/**
 * @mixin Model
 */
trait SyncHasMany {
    use SyncMany;

    /**
     * @template T of Model
     *
     * @param Collection<int, T>|EloquentCollection<int, T>|array<T> $objects
     */
    protected function syncHasMany(string $relation, Collection|array $objects): void {
        // Prepare
        /** @var HasMany<$this> $hasMany */
        $hasMany = (new ModelHelper($this))->getRelation($relation);

        if (!($hasMany instanceof HasMany)) {
            throw new InvalidArgumentException(sprintf(
                'The `$relation` must be instance of `%s`.',
                HasMany::class,
            ));
        }

        // Prepare
        $existing = $this->syncManyGetExisting($this, $relation)->keyBy(new GetKey());
        $children = (new EloquentCollection($objects))->map(new SetKey());

        foreach ($children as $child) {
            $key = $child->getKey();

            assert(is_string($key) || is_int($key));

            $existing->forget($key);
        }

        // Update relation
        $this->setRelation($relation, $children);

        // Update database
        if (!$children->isEmpty() || !$existing->isEmpty()) {
            $this->onSave(static function () use ($hasMany, $children, $existing): void {
                // Sync
                foreach ($children as $object) {
                    /** @var Model $object */
                    $hasMany->save($object);
                }

                // Delete unused
                /** @var Model $object */
                foreach ($existing as $object) {
                    $object->delete();
                }
            });
        }
    }
}
