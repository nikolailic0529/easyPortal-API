<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Organization as OrganizationModel;
use App\Services\Organization\RootOrganization;

class Organization {
    public function __construct(
        protected RootOrganization $root,
        protected Org $org,
    ) {
        // empty
    }

    public function root(OrganizationModel $organization): bool {
        return $this->root->is($organization);
    }

    /**
     * @return array<string,mixed>
     */
    public function branding(OrganizationModel $organization): array {
        return $this->org->branding($organization);
    }
}
