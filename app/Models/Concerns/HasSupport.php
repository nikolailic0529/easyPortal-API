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
trait HasSupport {
    public function support(): BelongsTo {
        return $this->belongsTo(Product::class);
    }

    public function setSupportAttribute(?Product $product): void {
        if ($product && $product->type !== ProductType::support()) {
            throw new InvalidArgumentException(sprintf(
                'The product must be type `%s`, `%s` given.',
                ProductType::asset(),
                $product->type,
            ));
        }

        $this->support()->associate($product);
    }
}
