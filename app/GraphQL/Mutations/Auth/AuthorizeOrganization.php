<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth;

use App\GraphQL\Queries\Me;
use App\Services\KeyCloak\KeyCloak;

/**
 * @deprecated
 */
class AuthorizeOrganization {
    public function __construct(
        protected KeyCloak $keycloak,
        protected Me $query,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return array<string, mixed>
     */
    public function __invoke(mixed $_, array $args): array {
        return [
            'me' => $this->query->getMe(
                $this->keycloak->authorize(null, $args['input']['code'], $args['input']['state']),
            ),
        ];
    }
}
