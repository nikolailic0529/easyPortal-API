<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Concerns\SyncHasMany;
use App\Utils\Eloquent\Model;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

use function app;
use function count;

/**
 * @template TPivot of \App\Utils\Eloquent\Model
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
    #[CascadeDelete(true)]
    public function locations(): HasMany {
        return $this->hasMany(
            $this->getLocationsModel()::class,
            $this->getLocationsForeignKey(),
        );
    }

    /**
     * @param Collection<int,TPivot>|array<TPivot> $locations
     */
    public function setLocationsAttribute(Collection|array $locations): void {
        $this->syncHasMany('locations', $locations);
        $this->locations_count = count($locations);
    }

    /**
     * @return HasOne<TPivot>
     */
    #[CascadeDelete(false)]
    public function headquarter(): HasOne {
        $type = (array) app()->make(Repository::class)->get('ep.headquarter_type');

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
