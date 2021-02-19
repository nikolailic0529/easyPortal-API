<?php declare(strict_types = 1);

namespace App\Providers;

use App\Services\Auth0\AuthService;
use App\Services\Auth0\UserRepository;
use Auth0\Login\Auth0Service;
use Auth0\Login\Contract\Auth0UserRepository;
use Auth0\SDK\Store\StoreInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     */
    public function register(): void {
        Date::use(CarbonImmutable::class);

        $this->registerAuth0();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {
        // empty
    }

    protected function registerAuth0(): void {
        $this->app->singleton(Auth0Service::class, AuthService::class);
        $this->app->singleton(AuthService::class, static function ($app): AuthService {
            return new AuthService(
                $app->make('config')->get('laravel-auth0'),
                $app->make(StoreInterface::class),
                $app->make('cache.store'),
            );
        });

        $this->app->bind(
            Auth0UserRepository::class,
            UserRepository::class,
        );
    }
}
