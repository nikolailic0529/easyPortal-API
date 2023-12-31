<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Services\Keycloak\Client\Client;
use App\Services\Organization\CurrentOrganization;

use function array_filter;

class ResetOrgUserPassword {
    public function __construct(
        protected Client $client,
        protected CurrentOrganization $organization,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return array<string, mixed>
     */
    public function __invoke(mixed $root, array $args): array {
        // Get user groups
        $organization = $this->organization->get();
        $groups       = $this->client->getUserGroups($args['input']['id']);
        $filtered     = array_filter($groups, static function ($group) use ($organization) {
            return $group->id === $organization->keycloak_group_id;
        });
        if (!$filtered) {
            throw new ResetOrgUserPasswordInvalidUser();
        }

        $this->client->requestResetPassword($args['input']['id']);

        return ['result' => true];
    }
}
