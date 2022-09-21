<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Kpi;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Model
 */
trait HasKpi {
    /**
     * @return BelongsTo<Kpi, self>
     */
    #[CascadeDelete]
    public function kpi(): BelongsTo {
        return $this->belongsTo(Kpi::class);
    }

    public function setKpiAttribute(?Kpi $kpi): void {
        // If KPI exists we need to delete it
        if ($kpi === null && $this->kpi_id) {
            $current = $this->kpi;

            if ($current) {
                $this->onSave(static function () use ($current): void {
                    $current->delete();
                });
            }
        }

        // Update
        $this->kpi()->associate($kpi);
    }
}
