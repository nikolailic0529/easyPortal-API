<?php declare(strict_types = 1);

namespace App\Services\Organization\Listeners;

use App\Models\Enums\OrganizationType;
use App\Models\Organization;
use App\Services\DataLoader\Events\ResellerUpdated;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Schema\Types\TranslationText;
use App\Services\I18n\Eloquent\TranslatedString;
use App\Services\Keycloak\Utils\Map;
use App\Utils\Providers\EventsProvider;
use Illuminate\Database\Eloquent\Builder;

class OrganizationUpdater implements EventsProvider {
    public function __construct(
        protected Normalizer $normalizer,
    ) {
        // empty
    }

    /**
     * @inheritDoc
     */
    public static function getEvents(): array {
        return [
            ResellerUpdated::class,
        ];
    }

    public function handle(ResellerUpdated $event): void {
        // Prepare
        $reseller = $event->getReseller();
        $company  = $event->getCompany();

        // Used?
        if (
            isset($company->keycloakClientScopeName)
            || isset($company->keycloakGroupId)
            || isset($company->keycloakName)
        ) {
            $existing = Organization::query()
                ->whereKeyNot($reseller->getKey())
                ->where(static function (Builder $query) use ($company): void {
                    if (isset($company->keycloakClientScopeName)) {
                        $query->orWhere('keycloak_scope', '=', $company->keycloakClientScopeName);
                    }

                    if (isset($company->keycloakGroupId)) {
                        $query->orWhere('keycloak_group_id', '=', $company->keycloakGroupId);
                    }

                    if (isset($company->keycloakName)) {
                        $query->orWhere('keycloak_name', '=', $company->keycloakName);
                    }
                })
                ->get();

            foreach ($existing as $organization) {
                if ($organization->keycloak_scope === ($company->keycloakClientScopeName ?? null)) {
                    $organization->keycloak_scope = null;
                }

                if ($organization->keycloak_group_id === ($company->keycloakGroupId ?? null)) {
                    $organization->keycloak_group_id = null;
                }

                if ($organization->keycloak_name === ($company->keycloakName ?? null)) {
                    $organization->keycloak_name = null;
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
                $organization->keycloak_name     = null;
                $organization->keycloak_scope    = null;
                $organization->keycloak_group_id = null;
                $organization->restore();
            }
        } else {
            $organization       = new Organization();
            $organization->id   = $reseller->getKey();
            $organization->type = OrganizationType::reseller();
        }

        // Update
        $organization->name = $reseller->name;

        if (isset($company->keycloakClientScopeName)) {
            $organization->keycloak_scope = $company->keycloakClientScopeName;
        }

        if (isset($company->keycloakGroupId)) {
            $organization->keycloak_group_id = $company->keycloakGroupId;
        }

        if (isset($company->keycloakName)) {
            $organization->keycloak_name = $company->keycloakName;
        }

        if (isset($company->brandingData)) {
            $branding                                       = $company->brandingData;
            $organization->analytics_code                   = $branding->resellerAnalyticsCode;
            $organization->branding_dark_theme              = $branding->brandingMode;
            $organization->branding_main_color              = $branding->mainColor;
            $organization->branding_secondary_color         = $branding->secondaryColor;
            $organization->branding_logo_url                = $branding->logoUrl;
            $organization->branding_favicon_url             = $branding->favIconUrl;
            $organization->branding_default_main_color      = $branding->defaultMainColor;
            $organization->branding_default_secondary_color = $branding->secondaryColorDefault;
            $organization->branding_default_logo_url        = $branding->defaultLogoUrl;
            $organization->branding_default_favicon_url     = $branding->useDefaultFavIcon;
            $organization->branding_welcome_image_url       = $branding->mainImageOnTheRight;
            $organization->branding_welcome_heading         = $this->getTranslatedString($branding->mainHeadingText);
            $organization->branding_welcome_underline       = $this->getTranslatedString($branding->underlineText);
        }

        // Save
        $organization->save();
    }

    /**
     * @param array<TranslationText>|null $translations
     */
    protected function getTranslatedString(?array $translations): ?TranslatedString {
        $string = null;

        if ($translations) {
            $string = new TranslatedString();

            foreach ($translations as $translation) {
                $text               = $translation->text;
                $locale             = $translation->language_code;
                $appLocale          = Map::getAppLocale($locale) ?? $locale;
                $string[$appLocale] = $text;
            }
        }

        return $string;
    }
}
