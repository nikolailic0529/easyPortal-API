<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\CurrentTenant;
use App\Models\Organization as ModelsOrganization;

class Organization {
    public function __construct(
        protected CurrentTenant $tenant,
    ) {
        // empty
    }
    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     */
    public function __invoke($_, array $args): ModelsOrganization {
        return $this->tenant->get();
    }
}