<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org\Role;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Services\KeyCloak\Client\Client;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Collection;

class Create {
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
    public function __invoke(Organization $organization, array $args): Role|bool {
        $input              = CreateInput::make($args['input']);
        $group              = $this->client->createGroup($organization, $input->name);
        $role               = new Role();
        $role->id           = $group->id;
        $role->name         = $group->name;
        $role->permissions  = $this->getPermissions($input->permissions);
        $role->organization = $organization;

        return $role->save() && $this->syncPermissions($role)
            ? $role
            : false;
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

    protected function syncPermissions(Role $role): bool {
        return $this->client->updateGroupRoles($role, $role->permissions->all());
    }
}
