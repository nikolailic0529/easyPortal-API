<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Organization as ModelsOrganization;
use App\Models\Permission;
use App\Services\KeyCloak\Client\Client;
use App\Services\Organization\CurrentOrganization;
use App\Services\Organization\RootOrganization;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

use function array_key_exists;
use function in_array;

class Organization {
    public function __construct(
        protected RootOrganization $root,
        protected Client $client,
        protected Repository $config,
        protected CurrentOrganization $currentOrganization,
    ) {
        // empty
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

    /**
     * @return array<string,mixed>
     */
    public function roles(ModelsOrganization $organization): ?array {
        $output = [];
        $group  = $this->client->getGroup($organization);
        if (!$group) {
            return null;
        }
        $clientId    = (string) $this->config->get('ep.keycloak.client_id');
        $permissions = Permission::all();
        foreach ($group->subGroups as $subGroup) {
            $output[] = [
                'id'          => $subGroup->id,
                'name'        => $subGroup->name,
                'permissions' => $this->transformPermission($subGroup->clientRoles, $clientId, $permissions),
            ];
        }

        return $output;
    }

    /**
     * @param array<string> $clientRoles
     *
     * @return array<string>
     */
    protected function transformPermission(array $clientRoles, string $clientId, Collection $permissions): array {
        $currentRoles = [];
        if (array_key_exists($clientId, $clientRoles)) {
            $currentRoles = $clientRoles[$clientId];
        }

        return $permissions->filter(static function ($permission) use ($currentRoles) {
            return in_array($permission->key, $currentRoles, true);
        })->map(static function ($permission) {
            return $permission->id;
        })->values()->all();
    }

    public function audits(ModelsOrganization $organization): HasMany {
        return $organization->audits();
    }
}
