<?php declare(strict_types = 1);

namespace App\Services\I18n\Translation;

use App\Services\I18n\Service;
use Illuminate\Support\Collection;

class TranslationDefaults extends TranslationLoader {
    public function __construct(
        protected Service $service,
        TranslationLoader $loader,
    ) {
        parent::__construct(
            $loader->app,
            $loader->disk,
            $loader->exceptionHandler,
            $loader->files,
            $loader->path,
        );
    }

    /**
     * @inheritDoc
     */
    public function getTranslations(string $locale): array {
        return parent::getTranslations($locale) + $this->loadModels($locale);
    }

    /**
     * @inheritDoc
     */
    protected function loadFallback(): array {
        return [];
    }

    /**
     * @inheritDoc
     */
    protected function loadStorage(string $locale): array {
        return [];
    }

    /**
     * @return array<string,string>
     */
    protected function loadModels(string $locale): array {
        // Default locale? (model's properties always in default locale)
        if ($this->app->getLocale() !== $locale) {
            return [];
        }

        // Load models
        $models       = $this->service->getTranslatableModels();
        $translations = [];

        foreach ($models as $model) {
            foreach ($this->getModels($model) as $translatable) {
                /** @var \App\Services\I18n\Contracts\Translatable $translatable */
                $translations += $translatable->getDefaultTranslations();
            }
        }

        return $translations;
    }

    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model&\App\Services\I18n\Contracts\Translatable> $model
     *
     * @return \Illuminate\Support\Collection<int,\App\Services\I18n\Contracts\Translatable>
     */
    protected function getModels(string $model): Collection {
        return $model::query()->translations()->get();
    }
}
