<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\Group;
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
        $input        = $args['input'];
        return ['updated' => $this->updateRole($organization, $input)];
    }

    /**
     * @param array<string,mixed> $input
     *
     * @return array<string,mixed>
     */
    public function updateRole(Organization $organization, array $input): array {
        $role = Role::query()
            ->whereKey($input['id'])
            ->where('organization_id', '=', $organization->getKey())
            ->first();

        if (array_key_exists('name', $input)) {
            // Update Role Name
            $name = $input['name'];
            $this->client->editSubGroup($role, $name);
            $role->name = $name;
            $role->save();
        }

        if (array_key_exists('name', $input)) {
            // update Permissions
            $this->syncPermissions($role, $input['permissions']);
        }

        return $this->transformGroup($role);
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

    /**
     * @return array<string,mixed>
     */
    public function transformGroup(Role $role): ?array {
        $group = $this->client->getGroup($role);
        return [
            'id'          => $group->id,
            'name'        => $group->name,
            'permissions' => $this->getPermissionsIds($group),
        ];
    }

    /**
     * @return array<string>
     */
    protected function getPermissionsIds(Group $group): array {
        $clientRoles  = $group->clientRoles;
        $clientId     = (string) $this->config->get('ep.keycloak.client_id');
        $currentRoles = [];
        if (array_key_exists($clientId, $clientRoles)) {
            $currentRoles = $clientRoles[$clientId];
        }

        return Permission::whereIn('key', $currentRoles)->pluck('id')->all();
    }
}
