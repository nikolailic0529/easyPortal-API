<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org\Role;

use App\Models\Permission;
use App\Models\Role;
use App\Services\KeyCloak\Client\Client;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Collection;

class Update {
    public function __construct(
        protected Client $client,
        protected CurrentOrganization $organization,
        protected Repository $config,
    ) {
        // empty
    }

    /**
     * @param array{input: array<mixed>} $args
     */
    public function __invoke(Role $role, array $args): bool {
        return $this->update($role, UpdateInput::make($args['input']));
    }

    public function update(Role $role, UpdateInput|CreateInput $input): bool {
        // Update name
        if (isset($input->name)) {
            $role->name = $input->name;
        }

        // Update permission
        if (isset($input->permissions)) {
            $role->permissions = $this->getPermissions($input->permissions);
        }

        // Return
        return $this->sync($role);
    }

    /**
     * @param array<string> $permissions
     *
     * @return \Illuminate\Support\Collection<\App\Models\Permission>
     */
    protected function getPermissions(array $permissions): Collection {
        return Permission::query()
            ->whereIn((new Permission())->getKeyName(), $permissions)
            ->get();
    }

    protected function sync(Role $role): bool {
        // Ensure that Role exists on KeyCloak
        $group                       = $this->client->createGroup($role->organization, $role->name);
        $role->{$role->getKeyName()} = $group->id;

        // Save
        return $role->save()
            && $this->client->updateGroup($role, $role->name)
            && $this->client->updateGroupRoles($role, $role->permissions->all());
    }
}
