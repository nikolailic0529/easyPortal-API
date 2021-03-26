<?php declare(strict_types = 1);

namespace App\Services;

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
        Filesystem $files,
        $path,
    ) {
        parent::__construct($files, $path);
    }

    /**
     * @inheritdoc
     */
    public function load($locale, $group, $namespace = null): array {
        $loaded = parent::load($locale, $group, $namespace);

        if ($group === '*' && $namespace === '*' && $locale !== $this->app->getFallbackLocale()) {
            $loaded += $this->loadJsonPaths($this->app->getFallbackLocale());
        }

        return $loaded;
    }
}
