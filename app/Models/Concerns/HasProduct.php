<?php declare(strict_types = 1);

namespace App\Models\Concerns;

use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

use function implode;
use function in_array;
use function sprintf;

/**
 * @mixin \App\Models\Model
 */
trait HasProduct {
    public function product(): BelongsTo {
        return $this->belongsTo(Product::class);
    }

    public function setProductAttribute(Product $product): void {
        if (!in_array($product->type, $this->getValidProductTypes(), true)) {
            throw new InvalidArgumentException(sprintf(
                'The product must be type `%s`, `%s` given.',
                implode(' | ', $this->getValidProductTypes()),
                $product->type,
            ));
        }

        $this->product()->associate($product);
    }

    /**
     * @return array<\App\Models\Enums\ProductType>
     */
    abstract protected function getValidProductTypes(): array;
}
