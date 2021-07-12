<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\User;
use App\Services\Organization\CurrentOrganization;

class DisableOrgUser {
    public function __construct(
        protected Client $client,
        protected CurrentOrganization $organization,
        protected EnableOrgUser $enableOrgUser,
    ) {
        // empty
    }
    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     *
     * @return array<string, mixed>
     */
    public function __invoke($_, array $args): array {
        if (!$this->enableOrgUser->checkUserInCurrentOrganization($args['input']['id'])) {
            throw new DisableOrgUserInvalidUser();
        }

        $user   = new User(['enabled' => false]);
        $result = $this->client->updateUser($args['input']['id'], $user);
        return ['result' => $result];
    }
}
