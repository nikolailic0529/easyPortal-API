<?php declare(strict_types = 1);

namespace App\Services\I18n\Translation;

use App\Services\Filesystem\Disks\AppDisk;
use App\Services\I18n\Exceptions\FailedToLoadTranslations;
use App\Services\I18n\Storages\AppTranslations;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Translation\FileLoader;

/**
 * By default Translator doesn't load fallback.json, so we should do this by hand.
 *
 * @see https://github.com/laravel/framework/issues/36760
 */
class TranslationLoader extends FileLoader {
    /**
     * @inheritdoc
     */
    public function __construct(
        protected Application $app,
        protected AppDisk $disk,
        protected ExceptionHandler $exceptionHandler,
        Filesystem $files,
        $path,
    ) {
        parent::__construct($files, $path);
    }

    /**
     * @return array<string,string>
     */
    public function getTranslations(string $locale): array {
        return $this->load($locale, '*', '*');
    }

    /**
     * @inheritdoc
     */
    public function load($locale, $group, $namespace = null): array {
        $loaded = parent::load($locale, $group, $namespace);

        if ($group === '*' && $namespace === '*') {
            // Custom translations (they have bigger priority)
            $loaded = $this->loadStorage($locale) + $loaded;

            // Fallback translations
            if ($locale !== $this->app->getFallbackLocale()) {
                $loaded += $this->loadFallback();
            }
        }

        // Return
        return $loaded;
    }

    /**
     * @return array<string, string>
     */
    protected function loadFallback(): array {
        return $this->loadStorage($this->app->getFallbackLocale())
            + $this->loadJsonPaths($this->app->getFallbackLocale());
    }

    /**
     * @return array<string, string>
     */
    protected function loadStorage(string $locale): array {
        try {
            return (new AppTranslations($this->disk, $locale))->load();
        } catch (Exception $exception) {
            $this->exceptionHandler->report(
                new FailedToLoadTranslations($locale, $exception),
            );
        }

        return [];
    }
}
