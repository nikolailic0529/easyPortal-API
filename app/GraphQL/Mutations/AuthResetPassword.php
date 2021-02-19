<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\Auth0\AuthService;

class AuthResetPassword {
    protected AuthService $service;

    public function __construct(AuthService $service) {
        $this->service = $service;
    }

    /**
     * @param array{username: string} $args
     */
    public function __invoke(mixed $_, array $args): bool {
        return $this->service->resetPassword($args['username']);
    }
}
