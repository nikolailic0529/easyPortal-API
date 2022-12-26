<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Data\ProductGroup;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Resolver\Resolvers\ProductGroupResolver;

/**
 * @mixin Factory
 */
trait WithProductGroup {
    abstract protected function getProductGroupResolver(): ProductGroupResolver;

    protected function productGroup(?string $key): ?ProductGroup {
        // Null?
        if ($key === null || $key === '') {
            return null;
        }

        // Find
        return $this->getProductGroupResolver()
            ->get($key, static function () use ($key): ProductGroup {
                $model       = new ProductGroup();
                $model->key  = $key;
                $model->name = $key;

                $model->save();

                return $model;
            });
    }
}
