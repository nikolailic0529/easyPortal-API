<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Concerns;

use App\Utils\Eloquent\Pivot;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot as EloquentPivot;
use Illuminate\Support\Collection;

use function reset;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends BelongsToMany<EloquentModel>
 */
class SyncBelongsToManyWrapper extends BelongsToMany {
    /**
     * @param BelongsToMany<TModel> $belongsToMany
     */
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
}
