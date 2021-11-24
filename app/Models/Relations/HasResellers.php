<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Reseller;
use App\Services\Organization\Eloquent\OwnedByReseller;
use App\Utils\Eloquent\Concerns\SyncBelongsToMany;
use App\Utils\Eloquent\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @mixin \App\Utils\Eloquent\Model
 */
trait HasResellers {
    use OwnedByReseller;
    use SyncBelongsToMany;

    // <editor-fold desc="Relations">
    // =========================================================================
    public function resellers(): BelongsToMany {
        $pivot = $this->getResellersPivot();

        return $this
            ->belongsToMany(
                Reseller::class,
                $pivot->getTable(),
                foreignPivotKey: $this->getResellersForeignPivotKey(),
                parentKey: $this->getResellersParentKey(),
            )
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }

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
