<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Status;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Concerns\SyncBelongsToMany;
use App\Utils\Eloquent\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

use function count;

/**
 * @property int $statuses_count
 *
 * @mixin \App\Utils\Eloquent\Model
 */
trait HasStatuses {
    use SyncBelongsToMany;

    #[CascadeDelete(true)]
    public function statuses(): BelongsToMany {
        $pivot = $this->getStatusesPivot();

        return $this
            ->belongsToMany(Status::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }

    /**
     * @param \Illuminate\Support\Collection|array<\App\Models\Status> $statuses
     */
    public function setStatusesAttribute(Collection|array $statuses): void {
        $this->syncBelongsToMany('statuses', $statuses);
        $this->statuses_count = count($this->statuses);
    }

    abstract protected function getStatusesPivot(): Pivot;
}
