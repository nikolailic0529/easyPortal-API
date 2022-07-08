<?php declare(strict_types = 1);

namespace App\Services\Organization\Listeners;

use App\Events\Subscriber;
use App\Models\Organization;
use App\Services\DataLoader\Events\ResellerUpdated;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Schema\TranslationText;
use App\Services\I18n\Eloquent\TranslatedString;
use App\Services\Keycloak\Utils\Map;
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
            $organization     = new Organization();
            $organization->id = $reseller->getKey();
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
                $text               = $this->normalizer->string($translation->text);
                $locale             = $this->normalizer->string($translation->language_code);
                $appLocale          = Map::getAppLocale($locale) ?? $locale;
                $string[$appLocale] = $text;
            }
        }

        return $string;
    }
}
