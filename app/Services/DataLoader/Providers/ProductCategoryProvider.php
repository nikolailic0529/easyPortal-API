<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Providers;

use App\Models\ProductCategory;
use App\Services\DataLoader\Cache\ClosureKey;
use App\Services\DataLoader\Provider;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class ProductCategoryProvider extends Provider {
    public function get(string $name, Closure $factory): ProductCategory {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve($name, $factory);
    }

    protected function getInitialQuery(): ?Builder {
        return ProductCategory::query();
    }

    /**
     * @inheritdoc
     */
    protected function getKeyRetrievers(): array {
        return [
            'name' => new ClosureKey(static function (ProductCategory $category): string {
                return $category->name;
            }),
        ];
    }
}
