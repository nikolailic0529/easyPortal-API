<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\GraphQL\Mutations\DisableUser;

/**
 * @deprecated
 */
class DisableOrgUser {
    public function __construct(
        protected DisableUser $mutation,
    ) {
        // empty
    }

    /**
     * @param null                 $_
     * @param array<string, mixed> $args
     *
     * @return array<string, mixed>
     */
    public function __invoke($_, array $args): array {
        return ($this->mutation)($_, $args);
    }
}
