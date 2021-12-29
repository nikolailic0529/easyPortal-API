<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\GraphQL\Mutations\Org\Role\Update;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Database\Eloquent\Collection;

use function array_key_exists;

/**
 * @deprecated
 */
class UpdateOrgRole {
    public function __construct(
        protected CurrentOrganization $organization,
        protected Update $mutation,
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
        $role         = Role::query()
            ->whereKey($args['input']['id'])
            ->where('organization_id', '=', $organization->getKey())
            ->first();

        unset($args['input']['id']);

        ($this->mutation)($role, $args);

        return [
            'updated' => $role,
        ];
    }

    /**
     * @param array<string,mixed> $input
     *
     * @return array<string,mixed>
     */
    public function updateRole(Organization $organization, array $input): Role {
        $role = Role::query()
            ->whereKey($input['id'])
            ->where('organization_id', '=', $organization->getKey())
            ->first();

        if (array_key_exists('name', $input)) {
            // Update Role Name
            $name = $input['name'];
            $this->client->updateGroup($role, $name);
            $role->name = $name;
            $role->save();
        }

        if (array_key_exists('permissions', $input)) {
            $permissions = $this->savePermissions($role, $input['permissions']);
            $this->syncPermissions($role, $permissions);
        }

        return $role;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection<\App\Models\Permission> $permissions
     */
    protected function syncPermissions(Role $role, Collection $permissions): bool {
        return $this->client->updateGroupRoles($role, $permissions->all());
    }

    /**
     * @param array<string> $permissions
     */
    protected function savePermissions(Role $role, array $permissions): Collection {
        $permissions       = Permission::whereIn((new Permission())->getKeyName(), $permissions)->get();
        $role->permissions = $permissions;
        $role->save();

        return $permissions;
    }
}
