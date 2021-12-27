<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Models\Permission;
use App\Models\Role;
use App\Services\KeyCloak\Client\Client;
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
}
