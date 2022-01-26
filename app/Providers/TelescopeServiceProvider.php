<?php declare(strict_types = 1);

namespace App\Providers;

use App\Http\Controllers\Telescope\HomeController as AppTelescopeHomeController;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\Http\Controllers\HomeController as TelescopeHomeController;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider {
    /**
     * Register any application services.
     */
    public function register(): void {
        parent::register();

        $this->app->bind(TelescopeHomeController::class, AppTelescopeHomeController::class);

        // Telescope::night();

        $this->hideSensitiveRequestDetails();
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
    protected function gate(): void {
        Gate::define('viewTelescope', static function (?Authenticatable $user): bool {
            return false;
        });
    }
}
