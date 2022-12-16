<?php declare(strict_types = 1);

namespace App\Providers;

use App\Models\Asset;
use App\Models\AssetCoverage;
use App\Models\AssetTag;
use App\Models\AssetWarranty;
use App\Models\Audits\Audit;
use App\Models\ChangeRequest;
use App\Models\Contact;
use App\Models\ContactType;
use App\Models\Customer;
use App\Models\CustomerLocation;
use App\Models\CustomerLocationType;
use App\Models\CustomerStatus;
use App\Models\Data\City;
use App\Models\Data\Country;
use App\Models\Data\Coverage;
use App\Models\Data\Currency;
use App\Models\Data\Language;
use App\Models\Data\Location;
use App\Models\Data\Oem;
use App\Models\Data\Product;
use App\Models\Data\ProductGroup;
use App\Models\Data\ProductLine;
use App\Models\Data\Psp;
use App\Models\Data\ServiceGroup;
use App\Models\Data\ServiceLevel;
use App\Models\Data\Status;
use App\Models\Data\Tag;
use App\Models\Data\Team;
use App\Models\Data\Type;
use App\Models\Distributor;
use App\Models\Document;
use App\Models\DocumentEntry;
use App\Models\DocumentStatus;
use App\Models\File;
use App\Models\Invitation;
use App\Models\Kpi;
use App\Models\LocationCustomer;
use App\Models\LocationReseller;
use App\Models\Note;
use App\Models\OemGroup;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\PasswordReset;
use App\Models\Permission;
use App\Models\QuoteRequest;
use App\Models\QuoteRequestAsset;
use App\Models\QuoteRequestDocument;
use App\Models\QuoteRequestDuration;
use App\Models\Reseller;
use App\Models\ResellerCustomer;
use App\Models\ResellerLocation;
use App\Models\ResellerLocationType;
use App\Models\ResellerStatus;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Models\UserSearch;
use App\Utils\Validation\Validator;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Clockwork\Support\Laravel\ClockworkServiceProvider;
use Illuminate\Contracts\Validation\Factory as ValidatorFactoryContract;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Factory as ValidatorFactory;

use function config;
use function func_get_args;

class AppServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     */
    public function register(): void {
        $this->registerDate();
        $this->registerValidator();
        $this->registerClockwork();
    }

    protected function registerDate(): void {
        Date::use(CarbonImmutable::class);
        Date::serializeUsing(static function (Carbon|CarbonImmutable $date): string {
            return $date->toIso8601String();
        });
    }

    protected function registerValidator(): void {
        $this->app->afterResolving(
            ValidatorFactoryContract::class,
            static function (ValidatorFactoryContract $factory): void {
                if ($factory instanceof ValidatorFactory) {
                    $factory->resolver(static function (): ValidatorContract {
                        return new Validator(...func_get_args());
                    });
                }
            },
        );
    }

    protected function registerClockwork(): void {
        // Even if `CLOCKWORK_ENABLE=false` some services will be booted up,
        // this is unwanted behaviour. Moreover, it can break CI actions
        // (eg `package:discover` command when no database). So are we disabled
        // auto-discovery and load it by hand only if needed.
        //
        // https://github.com/itsgoingd/clockwork/issues/444#issuecomment-759626114

        if (config('clockwork.enable') !== false) {
            $this->app->register(ClockworkServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {
        $this->bootMorphMap();
    }

    protected function bootMorphMap(): void {
        Relation::requireMorphMap(true);
        Relation::morphMap([
            'Asset'                => Asset::class,
            'AssetCoverage'        => AssetCoverage::class,
            'AssetTag'             => AssetTag::class,
            'AssetWarranty'        => AssetWarranty::class,
            'Audit'                => Audit::class,
            'ChangeRequest'        => ChangeRequest::class,
            'City'                 => City::class,
            'Contact'              => Contact::class,
            'ContactType'          => ContactType::class,
            'Country'              => Country::class,
            'Coverage'             => Coverage::class,
            'Currency'             => Currency::class,
            'Customer'             => Customer::class,
            'CustomerLocation'     => CustomerLocation::class,
            'CustomerLocationType' => CustomerLocationType::class,
            'CustomerStatus'       => CustomerStatus::class,
            'Distributor'          => Distributor::class,
            'Document'             => Document::class,
            'DocumentEntry'        => DocumentEntry::class,
            'DocumentStatus'       => DocumentStatus::class,
            'File'                 => File::class,
            'Invitation'           => Invitation::class,
            'Kpi'                  => Kpi::class,
            'Language'             => Language::class,
            'Location'             => Location::class,
            'LocationCustomer'     => LocationCustomer::class,
            'LocationReseller'     => LocationReseller::class,
            'Note'                 => Note::class,
            'Oem'                  => Oem::class,
            'OemGroup'             => OemGroup::class,
            'Organization'         => Organization::class,
            'OrganizationUser'     => OrganizationUser::class,
            'PasswordReset'        => PasswordReset::class,
            'Permission'           => Permission::class,
            'Product'              => Product::class,
            'ProductGroup'         => ProductGroup::class,
            'ProductLine'          => ProductLine::class,
            'Psp'                  => Psp::class,
            'QuoteRequest'         => QuoteRequest::class,
            'QuoteRequestAsset'    => QuoteRequestAsset::class,
            'QuoteRequestDocument' => QuoteRequestDocument::class,
            'QuoteRequestDuration' => QuoteRequestDuration::class,
            'Reseller'             => Reseller::class,
            'ResellerCustomer'     => ResellerCustomer::class,
            'ResellerLocation'     => ResellerLocation::class,
            'ResellerLocationType' => ResellerLocationType::class,
            'ResellerStatus'       => ResellerStatus::class,
            'Role'                 => Role::class,
            'RolePermission'       => RolePermission::class,
            'ServiceGroup'         => ServiceGroup::class,
            'ServiceLevel'         => ServiceLevel::class,
            'Status'               => Status::class,
            'Tag'                  => Tag::class,
            'Team'                 => Team::class,
            'Type'                 => Type::class,
            'User'                 => User::class,
            'UserSearch'           => UserSearch::class,
        ]);
    }
}
