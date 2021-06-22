<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Roles;

use App\Models\Role;
use App\Services\KeyCloak\Client\Client;
use App\Services\Organization\CurrentOrganization;

class UpdateOrgRole {
    public function __construct(
        protected Client $client,
        protected CurrentOrganization $organization,
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
        $role         = Role::query()
            ->whereKey($args['input']['id'])
            ->where('organization_id', '=', $organization->getKey())
            ->first();
        $this->client->editSubGroup($role, $name);
        $role->name = $name;
        $role->save();
        return ['updated' => $role->fresh()];
    }
}
