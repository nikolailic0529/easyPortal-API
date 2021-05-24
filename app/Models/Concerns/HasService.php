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
trait HasService {
    public function service(): BelongsTo {
        return $this->belongsTo(Product::class);
    }

    public function setServiceAttribute(?Product $product): void {
        if ($product->type !== ProductType::service()) {
            throw new InvalidArgumentException(sprintf(
                'The product must be type `%s`, `%s` given.',
                ProductType::asset(),
                $product->type,
            ));
        }

        $this->service()->associate($product);
    }
}
