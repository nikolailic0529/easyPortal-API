<?php declare(strict_types = 1);

namespace App\Providers;

use App\Services\Filesystem\Disks\AppDisk;
use App\Services\I18n\TranslationLoader;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Application;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\TranslationServiceProvider as IlluminateTranslationServiceProvider;

class TranslationServiceProvider extends IlluminateTranslationServiceProvider {
    protected function registerLoader(): void {
        $this->app->alias('translation.loader', TranslationLoader::class);
        $this->app->singleton('translation.loader', static function (Application $app): FileLoader {
            return new TranslationLoader(
                $app,
                $app->make(AppDisk::class),
                $app->make(ExceptionHandler::class),
                $app['files'],
                $app['path.lang'],
            );
        });
    }
}
