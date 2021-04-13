<?php declare(strict_types = 1);

namespace App\Services;

use App\Services\Filesystem\Disks\AppDisk;
use App\Services\Filesystem\Storages\AppTranslations;
use Exception;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Translation\FileLoader;
use Psr\Log\LoggerInterface;

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
        protected LoggerInterface $logger,
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

        if ($group === '*' && $namespace === '*') {
            // Custom translations (they have bigger priority)
            $loaded = $this->loadStorage($locale) + $loaded;

            // Fallback translations
            if ($locale !== $this->app->getFallbackLocale()) {
                $loaded += []
                    + $this->loadStorage($this->app->getFallbackLocale())
                    + $this->loadJsonPaths($this->app->getFallbackLocale());
            }
        }

        // Return
        return $loaded;
    }

    /**
     * @return array<mixed>
     */
    protected function loadStorage(string $locale): array {
        try {
            return (new AppTranslations($this->disk, $locale))->load();
        } catch (Exception $exception) {
            $this->logger->error('Failed to load custom translation file.', [
                'locale'    => $locale,
                'exception' => $exception,
            ]);
        }

        return [];
    }
}
