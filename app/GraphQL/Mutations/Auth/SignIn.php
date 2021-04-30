<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth;

use App\Models\Organization;
use App\Services\KeyCloak\KeyCloak;

class SignIn {
    public function __construct(
        protected KeyCloak $keycloak,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return array<string, mixed>
     */
    public function __invoke(mixed $_, array $args): array {
        $organization = Organization::query()->whereKey($args['input']['organization_id'])->first();
        $url          = $this->keycloak->getAuthorizationUrl($organization);

        return [
            'url' => $url,
        ];
    }
}
