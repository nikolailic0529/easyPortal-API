<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth\Organization;

use App\Models\Organization;
use App\Services\KeyCloak\KeyCloak;

class SignIn {
    public function __construct(
        protected KeyCloak $keycloak,
    ) {
        // empty
    }

    public function __invoke(Organization $organization): mixed {
        return [
            'result' => true,
            'url'    => $this->keycloak->getAuthorizationUrl($organization),
        ];
    }
}
