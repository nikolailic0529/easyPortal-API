<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Services\Auth0\AuthService;

class AuthSignIn {
    protected AuthService $service;

    public function __construct(AuthService $service) {
        $this->service = $service;
    }

    /**
     * @param array<string, mixed> $args
     */
    public function __invoke(mixed $_, array $args): string {
        return $this->service->getSignInLink();
    }
}
