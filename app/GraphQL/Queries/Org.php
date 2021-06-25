<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Organization;
use App\Services\Organization\CurrentOrganization;

class Org {
    public function __construct(
        protected CurrentOrganization $current,
    ) {
        // empty
    }

    /**
     * @param null                 $_
     * @param array<string, mixed> $args
     */
    public function __invoke($_, array $args): ?Organization {
        return $this->current->defined()
            ? $this->current->get()
            : null;
    }
}