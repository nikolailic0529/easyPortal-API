<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Currency;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin \App\Utils\Eloquent\Model
 */
trait HasCurrency {
    #[CascadeDelete(false)]
    public function currency(): BelongsTo {
        return $this->belongsTo(Currency::class);
    }

    public function setCurrencyAttribute(?Currency $currency): void {
        $this->currency()->associate($currency);
    }
}
