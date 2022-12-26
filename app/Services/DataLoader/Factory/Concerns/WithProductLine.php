<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Data\ProductLine;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\ProductLineResolver;

/**
 * @mixin Factory
 */
trait WithProductLine {
    abstract protected function getNormalizer(): Normalizer;

    abstract protected function getProductLineResolver(): ProductLineResolver;

    protected function productLine(?string $key): ?ProductLine {
        // Null?
        if ($key === null || $key === '') {
            return null;
        }

        // Find
        return $this->getProductLineResolver()
            ->get($key, static function () use ($key): ProductLine {
                $model       = new ProductLine();
                $model->key  = $key;
                $model->name = $key;

                $model->save();

                return $model;
            });
    }
}
