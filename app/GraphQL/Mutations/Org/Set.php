<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Models\Organization;
use App\Services\Organization\CurrentOrganization;

class Set {
    public function __construct(
        protected CurrentOrganization $organization,
    ) {
        // empty
    }

    /**
     * @param array{input: array{organization_id: string}} $args
     */
    public function __invoke(?Organization $org, array $args): bool {
        $organization = Organization::query()
            ->whereKey($args['input']['organization_id'])
            ->firstOrFail();
        $result       = $this->organization->set($organization);

        return $result;
    }
}
