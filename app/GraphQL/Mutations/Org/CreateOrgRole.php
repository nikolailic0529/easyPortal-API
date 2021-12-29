<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\GraphQL\Mutations\Org\Role\Create;
use App\Services\KeyCloak\Client\Client;
use App\Services\Organization\CurrentOrganization;

/**
 * @deprecated
 */
class CreateOrgRole {
    public function __construct(
        protected Client $client,
        protected CurrentOrganization $organization,
        protected Create $mutation,
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
        return [
            'created' => ($this->mutation)($this->organization->get(), $args),
        ];
    }
}
