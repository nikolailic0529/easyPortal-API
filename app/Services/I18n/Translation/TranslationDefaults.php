<?php declare(strict_types = 1);

namespace App\Services\I18n\Translation;

use App\Services\I18n\Contracts\Translatable;
use App\Services\I18n\Service;
use Illuminate\Database\Eloquent\Model;
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
    protected function loadLocale(string $locale): array {
        return parent::loadLocale($locale) + $this->loadModels($locale);
    }

    protected function getFallbackLocale(): ?string {
        return null;
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
        // Load models
        $models       = $this->service->getTranslatableModels();
        $translations = [];

        foreach ($models as $model) {
            foreach ($this->getModels($model) as $translatable) {
                /** @var Translatable $translatable */
                $translations += $translatable->getDefaultTranslations();
            }
        }

        return $translations;
    }

    /**
     * @param class-string<Model&Translatable> $model
     *
     * @return Collection<int,Model&Translatable>
     */
    protected function getModels(string $model): Collection {
        return $model::query()->get();
    }
}
