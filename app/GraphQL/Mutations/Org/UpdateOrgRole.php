<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Models\Permission;
use App\Models\Role;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\Role as KeyCloakRole;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Contracts\Config\Repository;

use function array_key_exists;
use function array_push;
use function array_search;

class UpdateOrgRole {
    public function __construct(
        protected Client $client,
        protected CurrentOrganization $organization,
        protected Repository $config,
    ) {
        // empty
    }

    /**
     * @param null                 $_
     * @param array<string, mixed> $args
     *
     * @return  array<string, mixed>
     */
    public function __invoke($_, array $args): array {
        $organization = $this->organization->get();
        $name         = $args['input']['name'];
        $permissions  = $args['input']['permissions'];
        $role         = Role::query()
            ->whereKey($args['input']['id'])
            ->where('organization_id', '=', $organization->getKey())
            ->first();
        // Update Role Name
        $this->client->editSubGroup($role, $name);
        $role->name = $name;
        $role->save();

        // Sync permissions
        $this->syncPermissions($role, $permissions);
        return ['updated' => $role->fresh()];
    }

    /**
     * @param array<string> $permissions
     */
    protected function syncPermissions(Role $role, array $permissions): void {
        // Get Current keycloak roles
        $group        = $this->client->getGroup($role);
        $clientId     = (string) $this->config->get('ep.keycloak.client_id');
        $clientRoles  = $group->clientRoles;
        $currentRoles = [];
        if (array_key_exists($clientId, $clientRoles)) {
            $currentRoles = $clientRoles[$clientId];
        }

        $permissions = Permission::whereIn((new Permission())->getKeyName(), $permissions)
            ->get()
            ->map(function ($permission) {
                // map to Roles
                return $this->transformPermission($permission);
            });

        $added = [];
        foreach ($permissions as $permission) {
            $key = array_search($permission->name, $currentRoles, true);
            if ($key !== false) {
                unset($currentRoles[$key]);
            } else {
                array_push($added, $permission->toArray());
            }
        }

        // Add new subgroup roles
        if (!empty($added)) {
            $this->client->addRolesToGroup($role, $added);
        }

        // Remove old permissions
        $deleted = [];
        foreach ($currentRoles as $currentRole) {
            $permission = Permission::where('key', '=', $currentRole)->first();
            if ($permission) {
                array_push($deleted, $this->transformPermission($permission));
            }
        }

        if (!empty($deleted)) {
            $this->client->removeRolesFromGroup($role, $deleted);
        }
    }

    protected function transformPermission(Permission $permission): KeyCloakRole {
        return new KeyCloakRole([
            'id'   => $permission->id,
            'name' => $permission->key,
        ]);
    }
}
