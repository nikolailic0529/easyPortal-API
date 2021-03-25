<?php declare(strict_types = 1);

namespace App\Models\Concerns;

use App\Models\Reseller;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin \App\Models\Model
 */
trait HasReseller {
    public function reseller(): BelongsTo {
        return $this->belongsTo(Reseller::class);
    }

    public function setResellerAttribute(?Reseller $reseller): void {
        $this->reseller()->associate($reseller);
    }
}
