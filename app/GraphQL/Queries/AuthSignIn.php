<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use RuntimeException;

class AuthSignIn {
    public function __construct() {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     */
    public function __invoke(mixed $_, array $args): string {
        // Should return link to sign in page
        throw new RuntimeException('FIXME [KeyCloak] Not implemented.');
    }
}
