<?php declare(strict_types = 1);

namespace App\Models\Concerns\Relations;

use App\Models\Concerns\SyncHasMany;
use App\Models\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

use function count;

/**
 * @template T of \App\Models\Model
 *
 * @property int $locations_count
 *
 * @mixin \App\Models\Model
 */
trait HasLocations {
    use SyncHasMany;

    public function locations(): HasMany {
        return $this->hasMany($this->getLocationsModel()::class);
    }

    /**
     * @param \Illuminate\Support\Collection<T>|array<T> $locations
     */
    public function setLocationsAttribute(Collection|array $locations): void {
        $this->syncMorphMany('locations', $locations);
        $this->locations_count = count($locations);
    }

    /**
     * @return T
     */
    abstract protected function getLocationsModel(): Model;
}
