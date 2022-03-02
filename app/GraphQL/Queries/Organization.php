<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Organization as OrganizationModel;
use App\Services\Keycloak\Client\Client;
use App\Services\Organization\CurrentOrganization;
use App\Services\Organization\RootOrganization;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;

class Organization {
    public function __construct(
        protected RootOrganization $root,
        protected Client $client,
        protected Repository $config,
        protected CurrentOrganization $currentOrganization,
    ) {
        // empty
    }

    public function root(OrganizationModel $organization): bool {
        return $this->root->is($organization);
    }

    /**
     * @return array<string,mixed>
     */
    public function branding(OrganizationModel $organization): array {
        return [
            'dark_theme'              => $organization->branding_dark_theme,
            'main_color'              => $organization->branding_main_color,
            'secondary_color'         => $organization->branding_secondary_color,
            'logo_url'                => $organization->branding_logo_url,
            'favicon_url'             => $organization->branding_favicon_url,
            'default_main_color'      => $organization->branding_default_main_color,
            'default_secondary_color' => $organization->branding_default_secondary_color,
            'default_logo_url'        => $organization->branding_default_logo_url,
            'default_favicon_url'     => $organization->branding_default_favicon_url,
            'welcome_image_url'       => $organization->branding_welcome_image_url,
            'welcome_heading'         => $organization->branding_welcome_heading,
            'welcome_underline'       => $organization->branding_welcome_underline,
            'dashboard_image_url'     => $organization->branding_dashboard_image_url,
        ];
    }

    public function audits(OrganizationModel $organization): Builder {
        return $organization->audits()->getQuery();
    }
}
