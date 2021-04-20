<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use RuntimeException;

class AuthResetPassword {
    public function __construct() {
        // empty
    }

    /**
     * @param array{username: string} $args
     */
    public function __invoke(mixed $_, array $args): bool {
        throw new RuntimeException('FIXME [KeyCloak] Not implemented.');
    }
}
