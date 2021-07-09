<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\User;
use App\Services\Organization\CurrentOrganization;

use function array_filter;

class EnableOrganizationUser {
    public function __construct(
        protected Client $client,
        protected CurrentOrganization $organization,
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
        if (!$this->checkUserInCurrentOrganization($args['input']['id'])) {
            throw new EnableOrganizationUserInvalidUser();
        }

        $user   = new User(['enabled' => true]);
        $result = $this->client->updateUser($args['input']['id'], $user);
        return ['result' => $result];
    }

    public function checkUserInCurrentOrganization(string $userId): bool {
        $organization = $this->organization->get();
        $groups       = $this->client->getUserGroups($userId);
        $filtered     = array_filter($groups, static function ($group) use ($organization) {
            return $group->id === $organization->keycloak_group_id;
        });
        return !empty($filtered);
    }
}
