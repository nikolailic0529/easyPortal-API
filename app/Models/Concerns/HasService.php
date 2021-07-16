<?php declare(strict_types = 1);

namespace App\Models\Concerns;

use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @deprecated
 *
 * @mixin \App\Models\Model
 */
trait HasService {
    public function service(): BelongsTo {
        return $this->belongsTo(Product::class);
    }

    public function setServiceAttribute(?Product $product): void {
        $this->service()->associate($product);
    }
}
