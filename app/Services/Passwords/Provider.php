<?php declare(strict_types = 1);

namespace App\Services\Passwords;

use Illuminate\Auth\Passwords\PasswordResetServiceProvider;

class Provider extends PasswordResetServiceProvider {
    protected function registerPasswordBroker(): void {
        parent::registerPasswordBroker();

        $this->app->singleton('auth.password', static function ($app) {
            return new PasswordBrokerManager($app);
        });
    }
}
