<?php declare(strict_types = 1);

namespace App\Models\Concerns\Relations;

use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin \App\Models\Model
 */
trait HasProduct {
    public function product(): BelongsTo {
        return $this->belongsTo(Product::class);
    }

    public function setProductAttribute(Product $product): void {
        $this->product()->associate($product);
    }
}