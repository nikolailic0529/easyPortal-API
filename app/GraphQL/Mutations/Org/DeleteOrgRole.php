<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Models\Role;
use App\Services\KeyCloak\Client\Client;
use App\Services\Organization\CurrentOrganization;

class DeleteOrgRole {
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
        $role         = Role::query()
            ->whereKey($args['input']['id'])
            ->where('organization_id', '=', $organization->getKey())
            ->first();
        $name         = $role->name;
        $this->client->deleteGroup($role);
        $role->delete();
        return ['deleted' => $name ];
    }
}
