<?php declare(strict_types = 1);

namespace App\Providers;

use App\Models\Asset;
use App\Models\City;
use App\Models\Contact;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Location;
use App\Models\Reseller;
use App\Models\Status;
use App\Models\Type;
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
            // Used in database
            'customer' => Customer::class,
            'contact'  => Contact::class,
            'location' => Location::class,
            'asset'    => Asset::class,
            'reseller' => Reseller::class,
            'document' => Document::class,

            // Used only for translation
            'type'     => Type::class,
            'status'   => Status::class,
            'country'  => Country::class,
            'city'     => City::class,
            'currency' => Currency::class,
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
