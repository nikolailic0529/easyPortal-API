<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Organization as ModelsOrganization;
use App\Services\Tenant\Tenant;

class Organization {
    public function __construct(
        protected Tenant $tenant,
    ) {
        // empty
    }

    /**
     * @param null                 $_
     * @param array<string, mixed> $args
     */
    public function __invoke($_, array $args): ModelsOrganization {
        return $this->tenant->get();
    }
}
