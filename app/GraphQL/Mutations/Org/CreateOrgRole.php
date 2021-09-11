<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Models\Permission;
use App\Models\Role;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\Group;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Collection;

use function array_key_exists;

class CreateOrgRole {
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
        $organization       = $this->organization->get();
        $group              = $this->client->createSubGroup($organization, $args['input']['name']);
        $role               = new Role();
        $role->id           = $group->id;
        $role->name         = $group->name;
        $role->organization = $organization;
        $role->save();

        if (array_key_exists('permissions', $args['input'])) {
            $permissions = $this->savePermissions($role, $args['input']['permissions']);
            $this->syncKeycloak($role, $permissions);
        }

        return ['created' => $role];
    }

    /**
     * @param array<string> $permissions
     */
    public function savePermissions(Role $role, array $permissions): Collection {
        $permissions       = Permission::whereIn((new Permission())->getKeyName(), $permissions)->get();
        $role->permissions = $permissions;
        $role->save();
        return $permissions;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection<\App\Models\Permission> $permissions
     */
    protected function syncKeycloak(Role $role, Collection $permissions): void {
        $keycloakPermissions = $permissions->map(static function ($permission) {
            return [
                'id'   => $permission->id,
                'name' => $permission->key,
            ];
        })->all();
        $this->client->addRolesToGroup($role, $keycloakPermissions);
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
