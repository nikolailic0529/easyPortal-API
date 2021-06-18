<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Roles;

use App\Models\Role;
use App\Services\KeyCloak\Client\Client;
use App\Services\Organization\CurrentOrganization;

class CreateOrgRole {
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
        $organization          = $this->organization->get();
        $group                 = $this->client->createSubGroup($organization, $args['input']['name']);
        $role                  = new Role();
        $role->id              = $group->id;
        $role->name            = $group->name;
        $role->organization_id = $organization->id;
        $role->save();
        return ['created' => $role];
    }
}
