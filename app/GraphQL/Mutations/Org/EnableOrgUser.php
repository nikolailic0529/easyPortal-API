<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\GraphQL\Mutations\EnableUser;

class EnableOrgUser {
    public function __construct(
        protected EnableUser $mutation,
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
