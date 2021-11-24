<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Kpi;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @mixin \App\Utils\Eloquent\Model
 */
trait HasKpi {
    public function kpi(): MorphOne {
        return $this->morphOne(Kpi::class, 'object');
    }

    public function setKpiAttribute(?Kpi $kpi): void {
        if ($kpi === null && $this->kpi) {
            $kpi = $this->kpi;

            $this->setRelation('kpi', null);
            $this->onSave(static function () use ($kpi): void {
                $kpi->delete();
            });
        } elseif ($kpi) {
            $kpi->object = $this;

            $this->setRelation('kpi', $kpi);
            $this->onSave(static function () use ($kpi): void {
                $kpi->save();
            });
        } else {
            // no action
        }
    }
}
