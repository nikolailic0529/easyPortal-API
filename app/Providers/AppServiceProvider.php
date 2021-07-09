<?php declare(strict_types = 1);

namespace App\Providers;

use App\Exceptions\GraphQLHandler;
use App\Models\Asset;
use App\Models\AssetCoverage;
use App\Models\AssetTag;
use App\Models\AssetWarranty;
use App\Models\AssetWarrantyService;
use App\Models\City;
use App\Models\Contact;
use App\Models\ContactType;
use App\Models\Country;
use App\Models\Coverage;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Distributor;
use App\Models\Document;
use App\Models\DocumentEntry;
use App\Models\Language;
use App\Models\Location;
use App\Models\LocationType;
use App\Models\Oem;
use App\Models\Organization;
use App\Models\PasswordReset;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Reseller;
use App\Models\ResellerCustomer;
use App\Models\Role;
use App\Models\Status;
use App\Models\Tag;
use App\Models\Type;
use App\Models\User;
use App\Models\UserSearch;
use App\Services\KeyCloak\KeyCloak;
use App\Services\KeyCloak\UserProvider;
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
        $this->bootMorphMap();
        $this->bootKeyCloak();
        $this->bootGraphQL($dispatcher);
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
            'asset'                  => Asset::class,
            'asset-coverage'         => AssetCoverage::class,
            'coverage'               => Coverage::class,
            'asset-tag'              => AssetTag::class,
            'asset-warranty'         => AssetWarranty::class,
            'asset-warranty-service' => AssetWarrantyService::class,
            'city'                   => City::class,
            'contact'                => Contact::class,
            'contact-type'           => ContactType::class,
            'country'                => Country::class,
            'currency'               => Currency::class,
            'customer'               => Customer::class,
            'distributor'            => Distributor::class,
            'document'               => Document::class,
            'document-entry'         => DocumentEntry::class,
            'language'               => Language::class,
            'location'               => Location::class,
            'location-type'          => LocationType::class,
            'oem'                    => Oem::class,
            'organization'           => Organization::class,
            'password-reset'         => PasswordReset::class,
            'product'                => Product::class,
            'reseller'               => Reseller::class,
            'reseller-customer'      => ResellerCustomer::class,
            'status'                 => Status::class,
            'tag'                    => Tag::class,
            'type'                   => Type::class,
            'user'                   => User::class,
            'user-search'            => UserSearch::class,
            'permission'             => Permission::class,
            'role'                   => Role::class,
        ]);
    }
}
