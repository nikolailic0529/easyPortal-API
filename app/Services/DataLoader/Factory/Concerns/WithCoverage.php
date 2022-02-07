<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Coverage;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\CoverageResolver;

/**
 * @mixin \App\Services\DataLoader\Factory\Factory
 */
trait WithCoverage {
    abstract protected function getNormalizer(): Normalizer;

    abstract protected function getCoverageResolver(): CoverageResolver;

    protected function coverage(string $coverage): Coverage {
        $coverage = $this->getCoverageResolver()->get($coverage, $this->factory(function () use ($coverage): Coverage {
            $model       = new Coverage();
            $normalizer  = $this->getNormalizer();
            $model->key  = $normalizer->string($coverage);
            $model->name = $normalizer->string($coverage);

            $model->save();

            return $model;
        }));

        return $coverage;
    }
}
