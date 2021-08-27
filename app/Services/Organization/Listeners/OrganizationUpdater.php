<?php declare(strict_types = 1);

namespace App\Services\Organization\Listeners;

use App\Events\Subscriber;
use App\Models\Organization;
use App\Services\DataLoader\Events\ResellerUpdated;
use App\Services\DataLoader\Normalizer;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;

class OrganizationUpdater implements Subscriber {
    public function __construct(
        protected Normalizer $normalizer,
    ) {
        // empty
    }

    public function subscribe(Dispatcher $dispatcher): void {
        $dispatcher->listen(ResellerUpdated::class, $this::class);
    }

    public function handle(ResellerUpdated $event): void {
        // Prepare
        $reseller = $event->getReseller();
        $company  = $event->getCompany();

        // Used?
        if (isset($company->keycloakName) || isset($company->keycloakGroupId)) {
            $existing = Organization::query()
                ->whereKeyNot($reseller->getKey())
                ->where(static function (Builder $query) use ($company): void {
                    if (isset($company->keycloakName)) {
                        $query->orWhere('keycloak_scope', '=', $company->keycloakName);
                    }

                    if (isset($company->keycloakGroupId)) {
                        $query->orWhere('keycloak_group_id', '=', $company->keycloakGroupId);
                    }
                })
                ->get();

            foreach ($existing as $organization) {
                if ($organization->keycloak_scope === ($company->keycloakName ?? null)) {
                    $organization->keycloak_scope = null;
                }

                if ($organization->keycloak_group_id === ($company->keycloakGroupId ?? null)) {
                    $organization->keycloak_group_id = null;
                }

                $organization->save();
            }
        }

        // Update
        $organization = Organization::query()
            ->whereKey($reseller->getKey())
            ->withTrashed()
            ->first();

        if ($organization) {
            if ($organization->trashed()) {
                $organization->keycloak_scope    = null;
                $organization->keycloak_group_id = null;
                $organization->restore();
            }
        } else {
            $organization                                = new Organization();
            $organization->{$organization->getKeyName()} = $reseller->getKey();
        }

        // Update
        $normalizer         = $this->normalizer;
        $organization->name = $reseller->name;

        if (isset($company->keycloakName)) {
            $organization->keycloak_scope = $this->normalizer->string($company->keycloakName);
        }

        if (isset($company->keycloakGroupId)) {
            $organization->keycloak_group_id = $this->normalizer->uuid($company->keycloakGroupId);
        }

        if (isset($company->brandingData)) {
            $branding                                       = $company->brandingData;
            $organization->analytics_code                   = $normalizer->string($branding->resellerAnalyticsCode);
            $organization->branding_dark_theme              = $normalizer->boolean($branding->brandingMode);
            $organization->branding_main_color              = $normalizer->color($branding->mainColor);
            $organization->branding_secondary_color         = $normalizer->color($branding->secondaryColor);
            $organization->branding_logo_url                = $normalizer->string($branding->logoUrl);
            $organization->branding_favicon_url             = $normalizer->string($branding->favIconUrl);
            $organization->branding_default_main_color      = $normalizer->color($branding->defaultMainColor);
            $organization->branding_default_secondary_color = $normalizer->color($branding->secondaryColorDefault);
            $organization->branding_default_logo_url        = $normalizer->string($branding->defaultLogoUrl);
            $organization->branding_default_favicon_url     = $normalizer->string($branding->useDefaultFavIcon);
            $organization->branding_welcome_image_url       = $normalizer->string($branding->mainImageOnTheRight);
            $organization->branding_welcome_heading         = $normalizer->string($branding->mainHeadingText);
            $organization->branding_welcome_underline       = $normalizer->string($branding->underlineText);
        }

        if (isset($company->companyKpis)) {
            $kpi                                       = $company->companyKpis;
            $organization->kpi_assets_total            = (int) $normalizer->number($kpi->totalAssets);
            $organization->kpi_assets_active           = (int) $normalizer->number($kpi->activeAssets);
            $organization->kpi_assets_covered          = (float) $normalizer->number($kpi->activeAssetsPercentage);
            $organization->kpi_customers_active        = (int) $normalizer->number($kpi->activeCustomers);
            $organization->kpi_customers_active_new    = (int) $normalizer->number($kpi->newActiveCustomers);
            $organization->kpi_contracts_active        = (int) $normalizer->number($kpi->activeContracts);
            $organization->kpi_contracts_active_amount = (float) $normalizer->number($kpi->activeContractTotalAmount);
            $organization->kpi_contracts_active_new    = (int) $normalizer->number($kpi->newActiveContracts);
            $organization->kpi_contracts_expiring      = (int) $normalizer->number($kpi->expiringContracts);
            $organization->kpi_quotes_active           = (int) $normalizer->number($kpi->activeQuotes);
            $organization->kpi_quotes_active_amount    = (float) $normalizer->number($kpi->activeQuotesTotalAmount);
            $organization->kpi_quotes_active_new       = (int) $normalizer->number($kpi->newActiveQuotes);
            $organization->kpi_quotes_expiring         = (int) $normalizer->number($kpi->expiringQuotes);
        }

        // Save
        $organization->save();
    }
}
