<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Utils\Eloquent\Concerns\SyncHasMany;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

use function config;
use function count;

/**
 * @template TPivot of Model
 *
 * @property int $locations_count
 *
 * @mixin Model
 */
trait HasLocations {
    use SyncHasMany;

    /**
     * @return HasMany<TPivot>
     */
    public function locations(): HasMany {
        return $this->hasMany(
            $this->getLocationsModel()::class,
            $this->getLocationsForeignKey(),
        );
    }

    /**
     * @param Collection<int,TPivot> $locations
     */
    public function setLocationsAttribute(Collection $locations): void {
        $this->syncHasMany('locations', $locations);
        $this->locations_count = count($locations);
    }

    /**
     * @return HasOne<TPivot>
     */
    public function headquarter(): HasOne {
        $type = (array) config('ep.headquarter_type');

        return $this
            ->hasOne(
                $this->getLocationsModel()::class,
                $this->getLocationsForeignKey(),
            )
            ->whereHasIn('types', static function ($query) use ($type) {
                return $query->whereIn($query->getModel()->getQualifiedKeyName(), $type);
            });
    }

    /**
     * @return TPivot
     */
    abstract protected function getLocationsModel(): Model;

    protected function getLocationsForeignKey(): ?string {
        return null;
    }
}
