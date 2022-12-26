<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Data\Coverage;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Normalizer\Normalizers\NameNormalizer;
use App\Services\DataLoader\Resolver\Resolvers\CoverageResolver;

/**
 * @mixin Factory
 */
trait WithCoverage {
    abstract protected function getCoverageResolver(): CoverageResolver;

    protected function coverage(string $coverage): Coverage {
        $coverage = $this->getCoverageResolver()->get($coverage, static function () use ($coverage): Coverage {
            $model       = new Coverage();
            $model->key  = $coverage;
            $model->name = NameNormalizer::normalize($coverage);

            $model->save();

            return $model;
        });

        return $coverage;
    }
}
