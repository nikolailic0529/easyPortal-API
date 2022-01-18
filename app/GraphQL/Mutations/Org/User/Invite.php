<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org\User;

use App\GraphQL\Mutations\Organization\User\Invite as Mutation;
use App\Models\Organization;

class Invite {
    public function __construct(
        protected Mutation $mutation,
    ) {
        // empty
    }

    /**
     * @param array{input: array<mixed>} $args
     */
    public function __invoke(Organization $root, array $args): bool {
        return ($this->mutation)($root, $args);
    }
}
