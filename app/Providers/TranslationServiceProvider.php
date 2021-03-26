<?php declare(strict_types = 1);

namespace App\Providers;

use App\Services\TranslationLoader;
use Illuminate\Foundation\Application;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\TranslationServiceProvider as IlluminateTranslationServiceProvider;

class TranslationServiceProvider extends IlluminateTranslationServiceProvider {
    protected function registerLoader(): void {
        $this->app->singleton('translation.loader', static function (Application $app): FileLoader {
            return new TranslationLoader($app, $app['files'], $app['path.lang']);
        });
    }
}
