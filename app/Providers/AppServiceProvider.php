<?php declare(strict_types = 1);

namespace App\Providers;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\Location;
use App\Services\Auth0\AuthService;
use App\Services\Auth0\UserRepository;
use Auth0\Login\Auth0Service;
use Auth0\Login\Contract\Auth0UserRepository;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Application;
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
        Relation::morphMap([
            'app/customer' => Customer::class,
            'app/contact'  => Contact::class,
            'app/location' => Location::class,
        ]);
    }

    protected function registerAuth0(): void {
        $this->app->singleton(Auth0Service::class, static function (Application $app): Auth0Service {
            return $app->make(AuthService::class)->getService();
        });

        $this->app->singleton(AuthService::class, static function (Application $app): AuthService {
            return new AuthService($app);
        });

        $this->app->bind(
            Auth0UserRepository::class,
            UserRepository::class,
        );
    }
}
