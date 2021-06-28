<?php declare(strict_types = 1);

namespace App\Providers;

use App\Http\Controllers\Horizon\HomeController as AppHorizonHomeController;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\HorizonApplicationServiceProvider;
use Laravel\Horizon\Http\Controllers\HomeController as HorizonHomeController;

class HorizonServiceProvider extends HorizonApplicationServiceProvider {
    public function register(): void {
        parent::register();

        $this->app->bind(HorizonHomeController::class, AppHorizonHomeController::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');

        // Horizon::night();
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void {
        Gate::define('viewHorizon', static function (Authenticatable|null $user): bool {
            return false;
        });
    }
}
