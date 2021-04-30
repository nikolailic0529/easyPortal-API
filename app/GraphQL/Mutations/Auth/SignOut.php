<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth;

use App\Services\KeyCloak\KeyCloak;

class SignOut {
    public function __construct(
        protected KeyCloak $keycloak,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return array<string,mixed>
     */
    public function __invoke(mixed $_, array $args): array {
        return [
            'url' => $this->keycloak->signOut(),
        ];
    }
}