<?php declare(strict_types = 1);

namespace App\Models\Concerns;

use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @deprecated
 *
 * @mixin \App\Models\Model
 */
trait HasSupport {
    public function support(): BelongsTo {
        return $this->belongsTo(Product::class);
    }

    public function setSupportAttribute(?Product $product): void {
        $this->support()->associate($product);
    }
}
