<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Data\Status;
use App\Utils\Eloquent\Concerns\SyncBelongsToMany;
use App\Utils\Eloquent\Model;
use App\Utils\Eloquent\Pivot;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use function count;

/**
 * @property int $statuses_count
 *
 * @mixin Model
 */
trait HasStatuses {
    use SyncBelongsToMany;

    /**
     * @return BelongsToMany<Status>
     */
    public function statuses(): BelongsToMany {
        $pivot = $this->getStatusesPivot();

        return $this
            ->belongsToMany(Status::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }

    /**
     * @param Collection<int, Status> $statuses
     */
    public function setStatusesAttribute(Collection $statuses): void {
        $this->syncBelongsToMany('statuses', $statuses);
        $this->statuses_count = count($this->statuses);
    }

    abstract protected function getStatusesPivot(): Pivot;
}
