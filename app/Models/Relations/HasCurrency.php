<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin \App\Models\Model
 */
trait HasCurrency {
    public function currency(): BelongsTo {
        return $this->belongsTo(Currency::class);
    }

    public function setCurrencyAttribute(?Currency $currency): void {
        $this->currency()->associate($currency);
    }
}
