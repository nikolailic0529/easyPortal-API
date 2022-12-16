<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Reseller;
use App\Utils\Eloquent\Concerns\SyncBelongsToMany;
use App\Utils\Eloquent\Model;
use App\Utils\Eloquent\Pivot;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection as BaseCollection;

/**
 * @template TPivot of Pivot
 *
 * @property-read Collection<int, Reseller> $resellers
 * @property BaseCollection<string,TPivot>  $resellersPivots
 *
 * @mixin Model
 */
trait HasResellers {
    use SyncBelongsToMany;

    // <editor-fold desc="Relations">
    // =========================================================================
    /**
     * @return BelongsToMany<Reseller>
     */
    public function resellers(): BelongsToMany {
        $pivot = $this->getResellersPivot();

        return $this
            ->belongsToMany(
                Reseller::class,
                $pivot->getTable(),
                foreignPivotKey: $this->getResellersForeignPivotKey(),
                parentKey      : $this->getResellersParentKey(),
            )
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }

    /**
     * @return HasMany<TPivot>
     */
    public function resellersPivots(): HasMany {
        $resellers = $this->resellers();
        $relation  = $this->hasMany(
            $resellers->getPivotClass(),
            $resellers->getForeignPivotKeyName(),
        );

        return $relation;
    }

    /**
     * @param array<string,TPivot>|BaseCollection<string,TPivot> $resellers
     */
    public function setResellersPivotsAttribute(BaseCollection|array $resellers): void {
        $this->syncBelongsToManyPivots('resellers', $resellers);
    }

    /**
     * @return TPivot
     */
    abstract protected function getResellersPivot(): Pivot;

    protected function getResellersParentKey(): ?string {
        return null;
    }

    protected function getResellersForeignPivotKey(): ?string {
        return null;
    }
    // </editor-fold>
}
