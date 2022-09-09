<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Data\Currency;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Model
 */
trait HasCurrency {
    /**
     * @return BelongsTo<Currency, self>
     */
    #[CascadeDelete(false)]
    public function currency(): BelongsTo {
        return $this->belongsTo(Currency::class);
    }

    public function setCurrencyAttribute(?Currency $currency): void {
        $this->currency()->associate($currency);
    }
}
