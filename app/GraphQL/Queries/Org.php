<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\CustomerLocation;
use App\Models\Kpi;
use App\Models\Organization;
use App\Models\ResellerLocation;
use App\Services\Organization\CurrentOrganization;

class Org {
    public function __construct(
        protected CurrentOrganization $current,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     */
    public function __invoke(mixed $root, array $args): ?Organization {
        return $this->current->defined()
            ? $this->current->get()
            : null;
    }

    /**
     * @return array<string,mixed>
     */
    public function branding(Organization $org): array {
        return [
            'dark_theme'              => $org->branding_dark_theme,
            'main_color'              => $org->branding_main_color,
            'secondary_color'         => $org->branding_secondary_color,
            'logo_url'                => $org->branding_logo_url,
            'favicon_url'             => $org->branding_favicon_url,
            'default_main_color'      => $org->branding_default_main_color,
            'default_secondary_color' => $org->branding_default_secondary_color,
            'default_logo_url'        => $org->branding_default_logo_url,
            'default_favicon_url'     => $org->branding_default_favicon_url,
            'welcome_image_url'       => $org->branding_welcome_image_url,
            'welcome_heading'         => $org->branding_welcome_heading,
            'welcome_underline'       => $org->branding_welcome_underline,
            'dashboard_image_url'     => $org->branding_dashboard_image_url,
        ];
    }

    public function kpi(Organization $org): ?Kpi {
        return $org->company?->kpi;
    }

    public function headquarter(Organization $org): ResellerLocation|CustomerLocation|null {
        return $org->company->headquarter
            ?? $org->company?->locations->first();
    }

    public function organization(Organization $org): Organization {
        return $org;
    }
}
