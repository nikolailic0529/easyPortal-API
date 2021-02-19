<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use Illuminate\Auth\AuthManager;

class Me {
    protected AuthManager $auth;

    public function __construct(AuthManager $auth) {
        $this->auth = $auth;
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return array<mixed>|null
     */
    public function __invoke(mixed $_, array $args): ?array {
        /** @var \App\Models\User|null $user */
        $user = $this->auth->user();

        return $user ? [
            'id'          => $user->getKey(),
            'given_name'  => $user->given_name,
            'family_name' => $user->family_name,
        ] : null;
    }
}
