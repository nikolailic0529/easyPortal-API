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
 * @template T of \App\Utils\Eloquent\Model
 *
 * @property int $locations_count
 *
 * @mixin \App\Utils\Eloquent\Model
 */
trait HasLocations {
    use SyncHasMany;

    #[CascadeDelete(true)]
    public function locations(): HasMany {
        return $this->hasMany(
            $this->getLocationsModel()::class,
            $this->getLocationsForeignKey(),
        );
    }

    /**
     * @param \Illuminate\Support\Collection<T>|array<T> $locations
     */
    public function setLocationsAttribute(Collection|array $locations): void {
        $this->syncHasMany('locations', $locations);
        $this->locations_count = count($locations);
    }

    #[CascadeDelete(false)]
    public function headquarter(): HasOne {
        $type = app()->make(Repository::class)->get('ep.headquarter_type');

        return $this
            ->hasOne(
                $this->getLocationsModel()::class,
                $this->getLocationsForeignKey(),
            )
            ->whereHasIn('types', static function ($query) use ($type) {
                return $query->whereKey($type);
            });
    }

    /**
     * @return T
     */
    abstract protected function getLocationsModel(): Model;

    protected function getLocationsForeignKey(): ?string {
        return null;
    }
}
