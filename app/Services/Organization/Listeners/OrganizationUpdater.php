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
                $organization->restore();
            }
        } else {
            $organization                                = new Organization();
            $organization->{$organization->getKeyName()} = $reseller->getKey();
        }

        // Update
        $organization->name = $reseller->name;

        if (isset($company->keycloakName)) {
            $organization->keycloak_scope = $this->normalizer->string($company->keycloakName);
        }

        if (isset($company->keycloakGroupId)) {
            $organization->keycloak_group_id = $this->normalizer->uuid($company->keycloakGroupId);
        }

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
        }

        // Save
        $organization->save();
    }
}
