<?php declare(strict_types = 1);

namespace Tests\Providers;

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
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

use function array_filter;
use function get_defined_vars;

class ModelsProvider {
    public function __construct() {
        // empty
    }

    /**
     * @return array<string, Model>
     */
    public function __invoke(TestCase $test): array {
        // Create
        $distributor                   = Distributor::factory()->create();
        $type                          = Type::factory()->create();
        $status                        = Status::factory()->create();
        $coverage                      = Coverage::factory()->create();
        $country                       = Country::factory()->create();
        $city                          = City::factory()->create([
            'country_id' => $country,
        ]);
        $currency                      = Currency::factory()->create();
        $language                      = Language::factory()->create();
        $permission                    = Permission::factory()->create();
        $psp                           = Psp::factory()->create();
        $tag                           = Tag::factory()->create();
        $team                          = Team::factory()->create();
        $oem                           = Oem::factory()->create();
        $product                       = Product::factory()->create([
            'oem_id' => $oem,
        ]);
        $productLine                   = ProductLine::factory()->create();
        $productGroup                  = ProductGroup::factory()->create();
        $serviceGroup                  = ServiceGroup::factory()->create([
            'oem_id' => $oem,
        ]);
        $serviceLevel                  = ServiceLevel::factory()->create([
            'oem_id'           => $oem,
            'service_group_id' => $serviceGroup,
        ]);
        $oemGroup                      = OemGroup::factory()->create([
            'oem_id' => $oem,
        ]);
        $location                      = Location::factory()->create([
            'country_id' => $country,
            'city_id'    => $city,
        ]);
        $organization                  = Organization::factory()->create([
            'type'        => (new Reseller())->getMorphClass(),
            'currency_id' => $currency,
        ]);
        $organizationRole              = Role::factory()->create([
            'organization_id' => $organization,
        ]);
        $organizationRolePermission    = RolePermission::factory()->create([
            'role_id'       => $organizationRole,
            'permission_id' => $permission,
        ]);
        $user                          = User::factory()->create([
            'organization_id' => $organization,
        ]);
        $userSearch                    = UserSearch::factory()->create([
            'user_id' => $user,
        ]);
        $userInvitation                = Invitation::factory()->create([
            'organization_id' => $organization,
            'sender_id'       => $user,
            'user_id'         => $user,
            'role_id'         => $organizationRole,
            'team_id'         => $team,
        ]);
        $organizationUser              = OrganizationUser::factory()->create([
            'organization_id' => $organization,
            'user_id'         => $user,
            'role_id'         => $organizationRole,
            'team_id'         => $team,
        ]);
        $organizationChangeRequest     = ChangeRequest::factory()->create([
            'organization_id' => $organization,
            'user_id'         => $user,
            'object_type'     => $organization->getMorphClass(),
            'object_id'       => $organization,
        ]);
        $organizationChangeRequestFile = File::factory()->create([
            'object_type' => $organizationChangeRequest->getMorphClass(),
            'object_id'   => $organizationChangeRequest,
        ]);
        $resellerKpi                   = Kpi::factory()->create();
        $reseller                      = Reseller::factory()->create([
            'id'     => $organization,
            'kpi_id' => $resellerKpi,
        ]);
        $resellerContact               = Contact::factory()->create([
            'object_type' => $reseller->getMorphClass(),
            'object_id'   => $reseller,
        ]);
        $resellerContactType           = ContactType::factory()->create([
            'contact_id' => $resellerContact,
            'type_id'    => $type,
        ]);
        $resellerStatus                = ResellerStatus::factory()->create([
            'reseller_id' => $reseller,
            'status_id'   => $status,
        ]);
        $resellerLocation              = ResellerLocation::factory()->create([
            'reseller_id' => $reseller,
            'location_id' => $location,
        ]);
        $resellerLocationType          = ResellerLocationType::factory()->create([
            'reseller_location_id' => $resellerLocation,
            'type_id'              => $type,
        ]);
        $resellerChangeRequest         = ChangeRequest::factory()->create([
            'organization_id' => $organization,
            'user_id'         => $user,
            'object_type'     => $reseller->getMorphClass(),
            'object_id'       => $reseller,
        ]);
        $resellerChangeRequestFile     = File::factory()->create([
            'object_type' => $resellerChangeRequest->getMorphClass(),
            'object_id'   => $resellerChangeRequest,
        ]);
        $locationReseller              = LocationReseller::factory()->create([
            'location_id' => $location,
            'reseller_id' => $reseller,
        ]);
        $customerKpi                   = Kpi::factory()->create();
        $customer                      = Customer::factory()->create([
            'kpi_id' => $customerKpi,
        ]);
        $customerContact               = Contact::factory()->create([
            'object_type' => $customer->getMorphClass(),
            'object_id'   => $customer,
        ]);
        $customerContactType           = ContactType::factory()->create([
            'contact_id' => $customerContact,
            'type_id'    => $type,
        ]);
        $customerStatus                = CustomerStatus::factory()->create([
            'customer_id' => $customer,
            'status_id'   => $status,
        ]);
        $customerLocation              = CustomerLocation::factory()->create([
            'customer_id' => $customer,
            'location_id' => $location,
        ]);
        $customerLocationType          = CustomerLocationType::factory()->create([
            'customer_location_id' => $customerLocation,
            'type_id'              => $type,
        ]);
        $customerChangeRequest         = ChangeRequest::factory()->create([
            'organization_id' => $organization,
            'user_id'         => $user,
            'object_type'     => $customer->getMorphClass(),
            'object_id'       => $customer,
        ]);
        $customerChangeRequestFile     = File::factory()->create([
            'object_type' => $customerChangeRequest->getMorphClass(),
            'object_id'   => $customerChangeRequest,
        ]);
        $locationCustomer              = LocationCustomer::factory()->create([
            'location_id' => $location,
            'customer_id' => $customer,
        ]);
        $resellerCustomerKpi           = Kpi::factory()->create();
        $resellerCustomer              = ResellerCustomer::factory()->create([
            'reseller_id' => $reseller,
            'customer_id' => $customer,
            'kpi_id'      => $resellerCustomerKpi,
        ]);
        $audit                         = Audit::factory()->create([
            'organization_id' => $organization,
            'user_id'         => $user,
            'object_id'       => $reseller->getKey(),
            'object_type'     => $reseller->getMorphClass(),
        ]);
        $asset                         = Asset::factory()->create([
            'oem_id'      => $oem,
            'type_id'     => $type,
            'status_id'   => $status,
            'product_id'  => $product,
            'reseller_id' => $reseller,
            'customer_id' => $customer,
            'location_id' => $location,
        ]);
        $assetContact                  = Contact::factory()->create([
            'object_type' => $asset->getMorphClass(),
            'object_id'   => $asset,
        ]);
        $assetContactType              = ContactType::factory()->create([
            'contact_id' => $assetContact,
            'type_id'    => $type,
        ]);
        $assetCoverage                 = AssetCoverage::factory()->create([
            'asset_id'    => $asset,
            'coverage_id' => $coverage,
        ]);
        $assetTag                      = AssetTag::factory()->create([
            'asset_id' => $asset,
            'tag_id'   => $tag,
        ]);
        $assetChangeRequest            = ChangeRequest::factory()->create([
            'organization_id' => $organization,
            'user_id'         => $user,
            'object_type'     => $asset->getMorphClass(),
            'object_id'       => $asset,
        ]);
        $assetChangeRequestFile        = File::factory()->create([
            'object_type' => $assetChangeRequest->getMorphClass(),
            'object_id'   => $assetChangeRequest,
        ]);
        $quoteRequest                  = QuoteRequest::factory()->create([
            'organization_id' => $organization,
            'customer_id'     => $customer,
            'user_id'         => $user,
            'type_id'         => $type,
            'oem_id'          => $oem,
        ]);
        $quoteRequestDuration          = QuoteRequestDuration::factory()->create();
        $quoteRequestFile              = File::factory()->create([
            'object_type' => $quoteRequest->getMorphClass(),
            'object_id'   => $quoteRequest,
        ]);
        $quoteRequestAsset             = QuoteRequestAsset::factory()->create([
            'asset_id'         => $asset,
            'request_id'       => $quoteRequest,
            'duration_id'      => $quoteRequestDuration,
            'service_level_id' => $serviceLevel,
        ]);
        $quoteRequestContact           = Contact::factory()->create([
            'object_type' => $quoteRequest->getMorphClass(),
            'object_id'   => $quoteRequest,
        ]);
        $quoteRequestContactType       = ContactType::factory()->create([
            'contact_id' => $quoteRequestContact,
            'type_id'    => $type,
        ]);
        $contract                      = Document::factory()->create([
            'distributor_id' => $distributor,
            'reseller_id'    => $reseller,
            'customer_id'    => $customer,
            'oem_id'         => $oem,
            'oem_group_id'   => $oemGroup,
            'currency_id'    => $currency,
            'language_id'    => $language,
            'type_id'        => $type,
            'is_hidden'      => false,
            'is_contract'    => true,
            'is_quote'       => false,
        ]);
        $contractStatus                = DocumentStatus::factory()->create([
            'document_id' => $contract,
            'status_id'   => $status,
        ]);
        $contractEntry                 = DocumentEntry::factory()->create([
            'document_id'      => $contract,
            'asset_id'         => $asset,
            'asset_type_id'    => $type,
            'service_group_id' => $serviceGroup,
            'service_level_id' => $serviceLevel,
            'product_id'       => $product,
            'currency_id'      => $currency,
            'language_id'      => $language,
            'product_line_id'  => $productLine,
            'product_group_id' => $productGroup,
            'psp_id'           => $psp,
        ]);
        $contractContact               = Contact::factory()->create([
            'object_type' => $contract->getMorphClass(),
            'object_id'   => $contract,
        ]);
        $contractContactType           = ContactType::factory()->create([
            'contact_id' => $contractContact,
            'type_id'    => $type,
        ]);
        $contractChangeRequest         = ChangeRequest::factory()->create([
            'organization_id' => $organization,
            'user_id'         => $user,
            'object_type'     => $contract->getMorphClass(),
            'object_id'       => $contract,
        ]);
        $contractChangeRequestFile     = File::factory()->create([
            'object_type' => $contractChangeRequest->getMorphClass(),
            'object_id'   => $contractChangeRequest,
        ]);
        $contractNote                  = Note::factory()->create([
            'organization_id'   => $organization,
            'document_id'       => $contract,
            'user_id'           => $user,
            'quote_request_id'  => $quoteRequest,
            'change_request_id' => $contractChangeRequest,
        ]);
        $contractNoteFile              = File::factory()->create([
            'object_type' => $contractNote->getMorphClass(),
            'object_id'   => $contractNote,
        ]);
        $quote                         = Document::factory()->create([
            'distributor_id' => $distributor,
            'reseller_id'    => $reseller,
            'customer_id'    => $customer,
            'oem_id'         => $oem,
            'oem_group_id'   => $oemGroup,
            'currency_id'    => $currency,
            'language_id'    => $language,
            'type_id'        => $type,
            'is_hidden'      => false,
            'is_contract'    => false,
            'is_quote'       => true,
        ]);
        $quoteStatus                   = DocumentStatus::factory()->create([
            'document_id' => $quote,
            'status_id'   => $status,
        ]);
        $quoteEntry                    = DocumentEntry::factory()->create([
            'document_id'      => $quote,
            'asset_id'         => $asset,
            'asset_type_id'    => $type,
            'service_group_id' => $serviceGroup,
            'service_level_id' => $serviceLevel,
            'product_id'       => $product,
            'currency_id'      => $currency,
            'language_id'      => $language,
            'product_line_id'  => $productLine,
            'product_group_id' => $productGroup,
            'psp_id'           => $psp,
        ]);
        $quoteContact                  = Contact::factory()->create([
            'object_type' => $quote->getMorphClass(),
            'object_id'   => $quote,
        ]);
        $quoteContactType              = ContactType::factory()->create([
            'contact_id' => $quoteContact,
            'type_id'    => $type,
        ]);
        $quoteChangeRequest            = ChangeRequest::factory()->create([
            'organization_id' => $organization,
            'user_id'         => $user,
            'object_type'     => $quote->getMorphClass(),
            'object_id'       => $quote,
        ]);
        $quoteChangeRequestFile        = File::factory()->create([
            'object_type' => $quoteChangeRequest->getMorphClass(),
            'object_id'   => $quoteChangeRequest,
        ]);
        $quoteNote                     = Note::factory()->create([
            'organization_id'   => $organization,
            'document_id'       => $quote,
            'user_id'           => $user,
            'quote_request_id'  => $quoteRequest,
            'change_request_id' => $quoteChangeRequest,
        ]);
        $quoteNoteFile                 = File::factory()->create([
            'object_type' => $quoteNote->getMorphClass(),
            'object_id'   => $quoteNote,
        ]);
        $assetWarranty                 = AssetWarranty::factory()->create([
            'asset_id'         => $asset,
            'type_id'          => $type,
            'status_id'        => $status,
            'reseller_id'      => $reseller,
            'customer_id'      => $customer,
            'document_id'      => $contract,
            'service_group_id' => $serviceGroup,
            'service_level_id' => $serviceLevel,
        ]);
        $quoteRequestDocument          = QuoteRequestDocument::factory()->create([
            'request_id'  => $quoteRequest,
            'document_id' => $contract,
            'duration_id' => $quoteRequestDuration,
        ]);

        $asset->warranty = $assetWarranty;
        $asset->save();

        // Settings
        $test->setSettings([
            'ep.headquarter_type' => $type->getKey(),
            'ep.contract_types'   => $type->getKey(),
            'ep.quote_types'      => $type->getKey(),
        ]);

        // Return
        $models = get_defined_vars();
        $models = array_filter($models, static function (mixed $var): bool {
            return $var instanceof Model;
        });

        return $models;
    }
}
