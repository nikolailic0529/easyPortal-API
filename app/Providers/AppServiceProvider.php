<?php declare(strict_types = 1);

namespace App\Providers;

use App\Exceptions\GraphQLHandler;
use App\Models\Asset;
use App\Models\AssetCoverage;
use App\Models\AssetTag;
use App\Models\AssetWarranty;
use App\Models\AssetWarrantyServiceLevel;
use App\Models\ChangeRequest;
use App\Models\City;
use App\Models\Contact;
use App\Models\ContactType;
use App\Models\Country;
use App\Models\Coverage;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\CustomerStatus;
use App\Models\Distributor;
use App\Models\Document;
use App\Models\DocumentEntry;
use App\Models\File;
use App\Models\Language;
use App\Models\Location;
use App\Models\LocationType;
use App\Models\Note;
use App\Models\Oem;
use App\Models\OemGroup;
use App\Models\Organization;
use App\Models\PasswordReset;
use App\Models\Permission;
use App\Models\Product;
use App\Models\QuoteRequest;
use App\Models\QuoteRequestAsset;
use App\Models\QuoteRequestDuration;
use App\Models\Reseller;
use App\Models\ResellerCustomer;
use App\Models\ResellerStatus;
use App\Models\Role;
use App\Models\ServiceGroup;
use App\Models\ServiceLevel;
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
            'Asset'                     => Asset::class,
            'AssetCoverage'             => AssetCoverage::class,
            'AssetTag'                  => AssetTag::class,
            'AssetWarranty'             => AssetWarranty::class,
            'AssetWarrantyServiceLevel' => AssetWarrantyServiceLevel::class,
            'City'                      => City::class,
            'Contact'                   => Contact::class,
            'ContactType'               => ContactType::class,
            'Country'                   => Country::class,
            'Coverage'                  => Coverage::class,
            'Currency'                  => Currency::class,
            'Customer'                  => Customer::class,
            'CustomerStatus'            => CustomerStatus::class,
            'Distributor'               => Distributor::class,
            'Document'                  => Document::class,
            'DocumentEntry'             => DocumentEntry::class,
            'Language'                  => Language::class,
            'Location'                  => Location::class,
            'LocationType'              => LocationType::class,
            'Oem'                       => Oem::class,
            'Organization'              => Organization::class,
            'PasswordReset'             => PasswordReset::class,
            'Permission'                => Permission::class,
            'Product'                   => Product::class,
            'Reseller'                  => Reseller::class,
            'ResellerCustomer'          => ResellerCustomer::class,
            'ResellerStatus'            => ResellerStatus::class,
            'Role'                      => Role::class,
            'Status'                    => Status::class,
            'Tag'                       => Tag::class,
            'Type'                      => Type::class,
            'User'                      => User::class,
            'UserSearch'                => UserSearch::class,
            'OemGroup'                  => OemGroup::class,
            'ServiceGroup'              => ServiceGroup::class,
            'ServiceLevel'              => ServiceLevel::class,
            'Note'                      => Note::class,
            'ChangeRequest'             => ChangeRequest::class,
            'File'                      => File::class,
            'QuoteRequest'              => QuoteRequest::class,
            'QuoteRequestDuration'      => QuoteRequestDuration::class,
            'QuoteRequestAsset'         => QuoteRequestAsset::class,
            'Organization'              => Organization::class,
        ]);
    }
}
