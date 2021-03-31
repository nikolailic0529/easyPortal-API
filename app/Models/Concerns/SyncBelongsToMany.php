<?php declare(strict_types = 1);

namespace App\Models\Concerns;

use App\Models\Model;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use InvalidArgumentException;

use function sprintf;

/**
 * @mixin \App\Models\Model
 */
trait SyncBelongsToMany {
    /**
     * @param \Illuminate\Support\Collection<\App\Models\Model>|array<\App\Models\Model> $objects
     */
    protected function syncBelongsToMany(string $relation, Collection|array $objects): void {
        // Prepare
        /** @var \Illuminate\Database\Eloquent\Relations\BelongsToMany $belongsToMany */
        $belongsToMany = $this->{$relation}();

        if (!($belongsToMany instanceof BelongsToMany)) {
            throw new InvalidArgumentException(sprintf(
                'The `$relation` must be instance of `%s`.',
                BelongsToMany::class,
            ));
        }

        // Object should exist
        $getKey   = static function (Model $model): string {
            return $model->getKey();
        };
        $objects  = new EloquentCollection($objects);
        $new      = $objects->map($getKey);
        $existing = new Collection();

        if ($this->exists) {
            $existing = $this->{$relation}->map($getKey);
        } else {
            $this->save();
        }

        if ($new->diff($existing)->isEmpty() && $existing->diff($new)->isEmpty()) {
            return;
        }

        // Sync
        $this->setRelation($relation, $objects);
        $belongsToMany->sync($objects->map(static function (Model $model): string {
            return $model->getKey();
        })->all());
    }
}
