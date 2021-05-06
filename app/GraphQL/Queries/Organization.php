<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Organization as ModelsOrganization;
use App\Services\Organization\Organization as OrganizationService;

class Organization {
    public function __construct(
        protected OrganizationService $organization,
    ) {
        // empty
    }

    /**
     * @param null                 $_
     * @param array<string, mixed> $args
     */
    public function __invoke($_, array $args): ModelsOrganization {
        return $this->organization->get();
    }
}
