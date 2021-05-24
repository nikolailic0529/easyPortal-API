<?php declare(strict_types = 1);

namespace App\Models\Concerns;

use App\Models\Enums\ProductType;
use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

use function sprintf;

/**
 * @mixin \App\Models\Model
 */
trait HasProduct {
    public function product(): BelongsTo {
        return $this->belongsTo(Product::class);
    }

    public function setProductAttribute(Product $product): void {
        if ($product->type !== ProductType::asset()) {
            throw new InvalidArgumentException(sprintf(
                'The product must be type `%s`, `%s` given.',
                ProductType::asset(),
                $product->type,
            ));
        }

        $this->product()->associate($product);
    }
}
