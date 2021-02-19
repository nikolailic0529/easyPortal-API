<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

class AuthSignInByPassword extends AuthSignIn {
    /**
     * @inheritdoc
     */
    protected function signIn(array $args): ?array {
        return $this->service->signInByPassword($args['username'], $args['password']);
    }
}
