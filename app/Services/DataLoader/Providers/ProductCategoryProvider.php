<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Providers;

use App\Models\Model;
use App\Models\ProductCategory;
use App\Services\DataLoader\Cache\ClosureKey;
use App\Services\DataLoader\Provider;
use Illuminate\Database\Eloquent\Builder;

class ProductCategoryProvider extends Provider {
    public function get(string $name): ProductCategory {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve($name, function () use ($name): Model {
            return $this->create($name);
        });
    }

    protected function create(string $name): ProductCategory {
        $category       = new ProductCategory();
        $category->name = $this->normalizer->string($name);

        $category->save();

        return $category;
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
            ] + parent::getKeyRetrievers();
    }
}
