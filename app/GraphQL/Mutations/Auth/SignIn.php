<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth;

use App\Services\Keycloak\Keycloak;

class SignIn {
    public function __construct(
        protected Keycloak $keycloak,
    ) {
        // empty
    }

    /**
     * @param array{input: array{email: string, password: string}} $args
     */
    public function __invoke(mixed $root, array $args): bool {
        $user = $this->keycloak->signIn(
            $args['input']['email'],
            $args['input']['password'],
        );

        return $user !== null;
    }
}
