<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Organization as ModelsOrganization;
use App\Services\Organization\CurrentOrganization;

class Organization {
    public function __construct(
        protected CurrentOrganization $organization,
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
