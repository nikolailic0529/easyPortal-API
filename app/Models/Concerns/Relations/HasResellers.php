<?php declare(strict_types = 1);

namespace App\Models\Concerns\Relations;

use App\Models\Concerns\SyncBelongsToMany;
use App\Models\Pivot;
use App\Models\Reseller;
use App\Services\Organization\Eloquent\OwnedByReseller;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

/**
 * @mixin \App\Models\Model
 */
trait HasResellers {
    use OwnedByReseller;
    use SyncBelongsToMany;

    // <editor-fold desc="Relations">
    // =========================================================================
    public function resellers(): BelongsToMany {
        $pivot = $this->getResellersPivot();

        return $this
            ->belongsToMany(Reseller::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }

    /**
     * @param \Illuminate\Support\Collection<\App\Models\Reseller>|array<\App\Models\Reseller> $resellers
     */
    public function setResellersAttribute(Collection|array $resellers): void {
        $this->syncBelongsToMany('resellers', $resellers);
    }

    abstract protected function getResellersPivot(): Pivot;
    // </editor-fold>

    // <editor-fold desc="OwnedByOrganization">
    // =========================================================================
    public function getOrganizationColumn(): string {
        return "resellers.{$this->resellers()->getModel()->getKeyName()}";
    }
    // </editor-fold>
}
