<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use Auth0\Login\Auth0Service;

class AuthSignInUrl {
    protected Auth0Service $auth;

    public function __construct(Auth0Service $auth) {
        $this->auth = $auth;
    }

    /**
     * @param array<string, mixed> $args
     */
    public function __invoke(mixed $_, array $args): string {
        return $this->auth
            ->login(null, null, [
                ['scope' => 'openid profile email'],
            ])
            ->getTargetUrl();
    }
}
