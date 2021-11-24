<?php declare(strict_types = 1);

namespace App\Models\Concerns;

use App\Utils\Eloquent\Callbacks\GetKey;
use App\Utils\Eloquent\Callbacks\SetKey;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use InvalidArgumentException;

use function sprintf;

/**
 * @mixin \App\Models\Model
 */
trait SyncHasMany {
    use SyncMany;

    /**
     * @param \Illuminate\Support\Collection<\App\Models\Model>|array<\App\Models\Model> $objects
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
        $existing = $this->syncManyGetExisting($this, $relation)->keyBy(new GetKey());
        $children = (new EloquentCollection($objects))->map(new SetKey())->keyBy(new GetKey());

        if (!$existing->isEmpty()) {
            foreach ($children as $child) {
                /** @var \Illuminate\Database\Eloquent\Model $child */
                if ($existing->has($child->getKey())) {
                    $existing->forget($child->getKey());
                }
            }
        }

        // Update relation
        $this->setRelation($relation, new EloquentCollection($objects));

        // Update database
        if (!$children->isEmpty() || !$existing->isEmpty()) {
            $this->onSave(static function () use ($hasMany, $children, $existing): void {
                // Sync
                foreach ($children as $object) {
                    /** @var \App\Models\Model $object */
                    $hasMany->save($object);
                }

                // Delete unused
                /** @var \App\Models\Model $object */
                foreach ($existing as $object) {
                    $object->delete();
                }
            });
        }
    }
}
