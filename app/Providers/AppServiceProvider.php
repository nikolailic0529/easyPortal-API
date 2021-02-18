<?php declare(strict_types = 1);

namespace App\Providers;

use App\Services\Auth0\UserRepository;
use Auth0\Login\Contract\Auth0UserRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     */
    public function register(): void {
        Date::use(CarbonImmutable::class);

        $this->app->bind(
            Auth0UserRepository::class,
            UserRepository::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {
        // empty
    }
}
