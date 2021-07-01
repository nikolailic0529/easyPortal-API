<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Services\Organization\CurrentOrganization;

class UpdateOrgRoles {
    public function __construct(
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
        $organization = $this->organization->get();
        $updated      = [];

        foreach ($args['input'] as $roleInput) {
            $updated[] = $this->updateOrgRole->updateRole($organization, $roleInput);
        }

        return ['updated' => $updated];
    }
}
