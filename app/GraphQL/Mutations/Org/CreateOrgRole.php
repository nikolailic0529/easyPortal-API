<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Models\Permission;
use App\Models\Role;
use App\Services\KeyCloak\Client\Client;
use App\Services\Organization\CurrentOrganization;

use function array_key_exists;

class CreateOrgRole {
    public function __construct(
        protected Client $client,
        protected CurrentOrganization $organization,
        protected UpdateOrgRole $updateOrgRole,
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
        $role = $role->fresh();

        if (array_key_exists('permissions', $args['input'])) {
            // Add permissions
            $this->addRolePermissions($role, $args['input']['permissions']);
        }

        $group = $this->updateOrgRole->transformGroup($role);

        return ['created' => $group];
    }

    /**
     * @param array<string> $permissions
     */
    protected function addRolePermissions(Role $role, array $permissions): void {
        $permissions = Permission::whereIn((new Permission())->getKeyName(), $permissions)
            ->get()
            ->map(static function ($permission) {
                return [
                    'id'   => $permission->id,
                    'name' => $permission->key,
                ];
            })
            ->all();
        $this->client->addRolesToGroup($role, $permissions);
    }
}
