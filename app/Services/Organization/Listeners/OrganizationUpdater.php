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

    public function subscribe(Dispatcher $events): void {
        $events->listen(ResellerUpdated::class, $this::class);
    }

    public function handle(ResellerUpdated $event): void {
        // Prepare
        $reseller = $event->getReseller();
        $company  = $event->getCompany();

        // Used?
        $existing = Organization::query()
            ->where(static function (Builder $query) use ($company): void {
                $query->orWhere('keycloak_scope', '=', $company->keycloakName);
                $query->orWhere('keycloak_group_id', '=', $company->keycloakGroupId);
            })
            ->get();

        foreach ($existing as $organization) {
            if ($organization->keycloak_scope === $company->keycloakName) {
                $organization->keycloak_scope = null;
            }

            if ($organization->keycloak_group_id === $company->keycloakGroupId) {
                $organization->keycloak_group_id = null;
            }

            $organization->save();
        }

        // Update
        $organization = Organization::query()
            ->whereKey($reseller->getKey())
            ->withTrashed()
            ->first();

        if ($organization) {
            if ($organization->trashed()) {
                $organization->restore();
            }
        } else {
            $organization                                = new Organization();
            $organization->{$organization->getKeyName()} = $reseller->getKey();
        }

        // Update
        $organization->name              = $reseller->name;
        $organization->keycloak_scope    = $this->normalizer->string($company->keycloakName);
        $organization->keycloak_group_id = $this->normalizer->uuid($company->keycloakGroupId);

        if (isset($company->brandingData)) {
            $branding                                       = $company->brandingData;
            $normalizer                                     = $this->normalizer;
            $organization->analytics_code                   = $normalizer->string($branding->resellerAnalyticsCode);
            $organization->branding_dark_theme              = $normalizer->boolean($branding->brandingMode);
            $organization->branding_main_color              = $normalizer->string($branding->mainColor);
            $organization->branding_secondary_color         = $normalizer->string($branding->secondaryColor);
            $organization->branding_logo_url                = $normalizer->string($branding->logoUrl);
            $organization->branding_favicon_url             = $normalizer->string($branding->favIconUrl);
            $organization->branding_default_main_color      = $normalizer->string($branding->defaultMainColor);
            $organization->branding_default_secondary_color = $normalizer->string($branding->secondaryColorDefault);
            $organization->branding_default_logo_url        = $normalizer->string($branding->defaultLogoUrl);
            $organization->branding_default_favicon_url     = $normalizer->string($branding->useDefaultFavIcon);
            $organization->branding_welcome_image_url       = $normalizer->string($branding->mainImageOnTheRight);
            $organization->branding_welcome_heading         = $normalizer->string($branding->mainHeadingText);
            $organization->branding_welcome_underline       = $normalizer->string($branding->underlineText);
        } else {
            $organization->analytics_code                   = null;
            $organization->branding_dark_theme              = null;
            $organization->branding_main_color              = null;
            $organization->branding_secondary_color         = null;
            $organization->branding_logo_url                = null;
            $organization->branding_favicon_url             = null;
            $organization->branding_default_main_color      = null;
            $organization->branding_default_secondary_color = null;
            $organization->branding_default_logo_url        = null;
            $organization->branding_default_favicon_url     = null;
            $organization->branding_welcome_image_url       = null;
            $organization->branding_welcome_heading         = null;
            $organization->branding_welcome_underline       = null;
        }

        // Save
        $organization->save();
    }
}
