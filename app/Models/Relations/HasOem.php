<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Oem;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin \App\Utils\Eloquent\Model
 */
trait HasOem {
    public function oem(): BelongsTo {
        return $this->belongsTo(Oem::class);
    }

    public function setOemAttribute(Oem $oem): void {
        $this->oem()->associate($oem);
    }
}