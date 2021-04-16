<?php declare(strict_types = 1);

namespace App\Models\Concerns;

use App\Models\Location;
use App\Models\PolymorphicModel;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

use function count;

/**
 * @property int $locations_count
 *
 * @mixin \App\Models\Model
 */
trait HasLocations {
    use SyncMorphMany;

    public function locations(): MorphMany {
        return $this->morphMany(Location::class, 'object');
    }

    /**
     * @param \Illuminate\Support\Collection|array<\App\Models\Location> $locations
     */
    public function setLocationsAttribute(Collection|array $locations): void {
        $this->syncMorphMany('locations', $locations);
        $this->locations_count = count($locations);
    }

    protected function syncMorphManyDelete(PolymorphicModel $model): void {
        if ($model instanceof Location) {
            /**
             * Location can be used by Assets, in this case, we cannot delete it
             * but we can set `object_id` to NULL and it will be removed by
             * {@see \App\Services\DataLoader\Jobs\LocationsCleanupCronJob}.
             */
            $model->object_id = null;
            $model->save();
        } else {
            $model->delete();
        }
    }
}
