<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Data\Language;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Resolver\Resolvers\LanguageResolver;

use function mb_strtolower;

/**
 * @mixin Factory
 */
trait WithLanguage {
    abstract protected function getLanguageResolver(): LanguageResolver;

    protected function language(?string $code): ?Language {
        $language = null;

        if ($code !== null) {
            $language = $this->getLanguageResolver()->get($code, static function () use ($code): Language {
                $model       = new Language();
                $model->code = mb_strtolower($code);
                $model->name = $code;

                $model->save();

                return $model;
            });
        }

        return $language;
    }
}
