<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Data\Language;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\LanguageResolver;

use function mb_strtolower;

/**
 * @mixin Factory
 */
trait WithLanguage {
    abstract protected function getNormalizer(): Normalizer;

    abstract protected function getLanguageResolver(): LanguageResolver;

    protected function language(?string $code): ?Language {
        $language = null;

        if ($code !== null) {
            $language = $this->getLanguageResolver()->get($code, function () use ($code): Language {
                $model       = new Language();
                $normalizer  = $this->getNormalizer();
                $model->code = mb_strtolower($normalizer->string($code));
                $model->name = $normalizer->string($code);

                $model->save();

                return $model;
            });
        }

        return $language;
    }
}
