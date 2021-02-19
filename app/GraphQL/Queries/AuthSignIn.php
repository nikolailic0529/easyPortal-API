<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Services\Auth0\AuthService;

class AuthSignIn {
    protected AuthService $auth;

    public function __construct(AuthService $auth) {
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
