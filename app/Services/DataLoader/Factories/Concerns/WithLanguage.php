<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Language;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\LanguageResolver;

/**
 * @mixin \App\Services\DataLoader\Factory
 */
trait WithLanguage {
    abstract protected function getNormalizer(): Normalizer;

    abstract protected function getLanguageResolver(): LanguageResolver;

    protected function language(?string $code): ?Language {
        $language = null;

        if ($code !== null) {
            $language = $this->getLanguageResolver()->get($code, $this->factory(function () use ($code): Language {
                $model       = new Language();
                $normalizer  = $this->getNormalizer();
                $model->code = $normalizer->string($code);
                $model->name = $normalizer->string($code);

                $model->save();

                return $model;
            }));
        }

        return $language;
    }
}
