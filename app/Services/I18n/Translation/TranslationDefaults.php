<?php declare(strict_types = 1);

namespace App\Services\I18n\Translation;

class TranslationDefaults extends TranslationLoader {
    public function __construct(TranslationLoader $loader) {
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
    protected function loadFallback(): array {
        return [];
    }

    /**
     * @inheritDoc
     */
    protected function loadStorage(string $locale): array {
        return [];
    }
}
