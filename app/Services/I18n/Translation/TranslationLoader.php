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

use function array_filter;
use function array_unique;
use function explode;
use function reset;

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
        $loaded = [];

        if ($group === '*' && $namespace === '*') {
            $previous = null;
            $fallback = $this->getFallbackLocale();
            $locales  = array_unique(array_filter([
                $locale,
                $this->getBaseLocale($locale),
                $fallback,
                $this->getBaseLocale($fallback),
            ]));

            foreach ($locales as $name) {
                if ($previous !== $name) {
                    $loaded += $this->loadLocale($name);
                }

                $previous = $name;
            }
        } else {
            $loaded = parent::load($locale, $group, $namespace);
        }

        // Return
        return $loaded;
    }

    /**
     * @return array<string, string>
     */
    protected function loadLocale(string $locale): array {
        return $this->loadStorage($locale)
            + $this->loadJsonPaths($locale);
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

    protected function getBaseLocale(?string $locale): ?string {
        $parts = explode('_', (string) $locale, 2);
        $base  = reset($parts);
        $base  = $base && $base !== $locale ? $base : null;

        return $base;
    }

    protected function getFallbackLocale(): ?string {
        return $this->app->getFallbackLocale();
    }
}
