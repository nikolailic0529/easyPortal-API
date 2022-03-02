<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth\Organization;

use App\Models\Organization;
use App\Services\Keycloak\Keycloak;

class SignIn {
    public function __construct(
        protected Keycloak $keycloak,
    ) {
        // empty
    }

    public function __invoke(Organization $organization): mixed {
        return [
            'result' => true,
            'url'    => $this->getUrl($organization),
        ];
    }

    public function getUrl(Organization $organization): string {
        return $this->keycloak->getAuthorizationUrl($organization);
    }
}
