<?php declare(strict_types = 1);

namespace App\Models\Concerns;

use App\Models\Location;
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
}
