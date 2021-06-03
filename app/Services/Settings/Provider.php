<?php declare(strict_types = 1);

namespace App\Services\Settings;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class Provider extends ServiceProvider {
    public function register(): void {
        parent::register();

        $this->app->booting(static function (Application $app): void {
            $app->make(Bootstraper::class)->bootstrap();
        });
    }
}
