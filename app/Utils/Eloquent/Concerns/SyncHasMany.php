<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Concerns;

use App\Utils\Eloquent\Callbacks\GetKey;
use App\Utils\Eloquent\ModelHelper;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use InvalidArgumentException;

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
     * @param Collection<int, T> $objects
     */
    protected function syncHasMany(string $relation, Collection $objects): void {
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
        $children = $objects->all();

        foreach ($children as $child) {
            $key = $child->getKey();

            if (is_string($key) || is_int($key)) {
                $existing->forget($key);
            }
        }

        // Update relation
        $this->setRelation($relation, EloquentCollection::make($children));

        // Update database
        if (!!$children || !$existing->isEmpty()) {
            $this->onSave(static function () use ($hasMany, $children, $existing): void {
                // Sync
                foreach ($children as $object) {
                    $hasMany->save($object);
                }

                // Delete unused
                foreach ($existing as $object) {
                    $object->delete();
                }
            });
        }
    }
}
