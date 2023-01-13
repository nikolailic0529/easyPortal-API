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
        // Empty?
        if ($code === null || $code === '') {
            return null;
        }

        // Create
        return $this->getLanguageResolver()->get($code, static function (?Language $model) use ($code): Language {
            if ($model) {
                return $model;
            }

            $model       = new Language();
            $model->code = mb_strtolower($code);
            $model->name = $code;

            $model->save();

            return $model;
        });
    }
}
