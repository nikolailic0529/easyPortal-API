<?php declare(strict_types = 1);

namespace App\Providers;

use App\Exceptions\GraphQLHandler;
use App\Models\Asset;
use App\Models\City;
use App\Models\Contact;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Language;
use App\Models\Location;
use App\Models\Organization;
use App\Models\Reseller;
use App\Models\Status;
use App\Models\Type;
use App\Services\KeyCloak\KeyCloak;
use App\Services\KeyCloak\UserProvider;
use App\Services\Settings\Bootstraper;
use Carbon\CarbonImmutable;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;
use Nuwave\Lighthouse\Events\ManipulateResult;

class AppServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     */
    public function register(): void {
        Date::use(CarbonImmutable::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(Dispatcher $dispatcher): void {
        $this->bootConfig();
        $this->bootMorphMap();
        $this->bootKeyCloak();
        $this->bootGraphQL($dispatcher);
    }

    protected function bootConfig(): void {
        $this->app->booted(static function (Application $app): void {
            $app->make(Bootstraper::class)->bootstrap();
        });
    }

    protected function bootKeyCloak(): void {
        $this->app->singleton(KeyCloak::class);
        $this->app->make(AuthManager::class)->provider(
            UserProvider::class,
            static function (Application $app, array $config) {
                return $app->make(UserProvider::class);
            },
        );
    }

    protected function bootGraphQL(Dispatcher $dispatcher): void {
        $dispatcher->listen(
            ManipulateResult::class,
            function (ManipulateResult $event): void {
                $event->result->setErrorFormatter($this->app->make(GraphQLHandler::class));
            },
        );
    }

    protected function bootMorphMap(): void {
        Relation::morphMap([
            // Used in database
            'customer'     => Customer::class,
            'contact'      => Contact::class,
            'location'     => Location::class,
            'asset'        => Asset::class,
            'reseller'     => Reseller::class,
            'document'     => Document::class,
            'organization' => Organization::class,

            // Used only for translation
            'type'         => Type::class,
            'status'       => Status::class,
            'country'      => Country::class,
            'city'         => City::class,
            'currency'     => Currency::class,
            'language'     => Language::class,
        ]);
    }
}
