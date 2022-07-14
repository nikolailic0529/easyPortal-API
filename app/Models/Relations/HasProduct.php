<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Product;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Model
 */
trait HasProduct {
    /**
     * @return BelongsTo<Product, self>
     */
    #[CascadeDelete(false)]
    public function product(): BelongsTo {
        return $this->belongsTo(Product::class);
    }

    public function setProductAttribute(?Product $product): void {
        $this->product()->associate($product);
    }
}
