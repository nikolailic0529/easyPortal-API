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


//        if (false && $group === '*' && $namespace === '*') {
//            try {
//                $fileSystem = $this->app->make(FileSystem::class);
//                $disc       = Disc::app();
//                $this->addJsonPath($fileSystem->disk($disc)->path('lang'));
//                $loaded += $this->loadJsonPaths($locale);
//            } catch (Exception $e) {
//                // It should not break the app and continue
//                // empty
//            }
//        }

        if ($group === '*' && $namespace === '*' && $locale !== $this->app->getFallbackLocale()) {
            $loaded += $this->loadJsonPaths($this->app->getFallbackLocale());
        }

        // Return
        return $loaded;
    }
}
