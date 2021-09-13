<?php declare(strict_types = 1);

namespace App\Services\Settings;

use App\Services\Settings\Bootstrapers\LoadEnvironmentVariables;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables as IlluminateLoadEnvironmentVariables;
use Illuminate\Support\ServiceProvider;

class Provider extends ServiceProvider {
    public function register(): void {
        parent::register();

        $this->app->bind(IlluminateLoadEnvironmentVariables::class, LoadEnvironmentVariables::class);

        $this->booting(static function (Application $app): void {
            $app->make(Bootstraper::class)->bootstrap();
        });
    }
}
