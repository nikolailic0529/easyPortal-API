<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Organization as ModelsOrganization;
use App\Services\KeyCloak\Client\Client;
use App\Services\Organization\CurrentOrganization;
use App\Services\Organization\RootOrganization;

class Org {
    public function __construct(
        protected RootOrganization $root,
        protected CurrentOrganization $current,
        protected Client $client,
    ) {
        // empty
    }

    /**
     * @param null                 $_
     * @param array<string, mixed> $args
     */
    public function __invoke($_, array $args): ?ModelsOrganization {
        return $this->current->defined()
            ? $this->current->get()
            : null;
    }

    public function root(ModelsOrganization $organization): bool {
        return $this->root->is($organization);
    }

    /**
     * @return array<string,mixed>
     */
    public function branding(ModelsOrganization $organization): array {
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
        ];
    }

    /**
     * @return array<mixed>
     */
    public function users(ModelsOrganization $organization): array {
        return $this->client->users($organization);
    }
}
