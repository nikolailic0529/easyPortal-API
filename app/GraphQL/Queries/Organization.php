<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Organization as ModelsOrganization;
use App\Services\Organization\CurrentOrganization;
use App\Services\Organization\RootOrganization;

class Organization {
    public function __construct(
        protected RootOrganization $root,
        protected CurrentOrganization $current,
    ) {
        // empty
    }

    /**
     * @param null                 $_
     * @param array<string, mixed> $args
     */
    public function __invoke($_, array $args): ?ModelsOrganization {
        return $this->current->defined()
            ? $this->current->get()
            : null;
    }

    public function root(?ModelsOrganization $organization): bool {
        return $this->root->is($organization);
    }
}
