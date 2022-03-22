<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Reseller;
use App\Services\Organization\Eloquent\OwnedByResellerImpl;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Concerns\SyncBelongsToMany;
use App\Utils\Eloquent\Model;
use App\Utils\Eloquent\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @template TPivot of \App\Utils\Eloquent\Pivot
 *
 * @property Collection<string,TPivot> $resellersPivots
 *
 * @mixin Model
 */
trait HasResellers {
    use OwnedByResellerImpl;
    use SyncBelongsToMany;

    // <editor-fold desc="Relations">
    // =========================================================================
    #[CascadeDelete(false)]
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

    #[CascadeDelete(true)]
    public function resellersPivots(): HasMany {
        $resellers = $this->resellers();
        $relation  = $this->hasMany(
            $resellers->getPivotClass(),
            $resellers->getForeignPivotKeyName(),
        );

        return $relation;
    }

    /**
     * @param array<string,TPivot>|Collection<string,TPivot> $resellers
     */
    public function setResellersPivotsAttribute(Collection|array $resellers): void {
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

    // <editor-fold desc="OwnedByOrganization">
    // =========================================================================
    public function getOrganizationColumn(): string {
        return "resellers.{$this->resellers()->getModel()->getKeyName()}";
    }
    // </editor-fold>
}
