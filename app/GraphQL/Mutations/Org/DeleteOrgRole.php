<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\GraphQL\Mutations\Org\Role\Delete;
use App\Models\Role;

/**
 * @deprecated
 */
class DeleteOrgRole {
    public function __construct(
        protected Delete $mutation,
    ) {
        // empty
    }

    /**
     * @param null                 $_
     * @param array<string, mixed> $args
     *
     * @return  array<string, mixed>
     */
    public function __invoke($_, array $args): array {
        $role = Role::query()
            ->whereKey($args['input']['id'])
            ->first();

        if ($role) {
            ($this->mutation)($role);
        }

        return ['deleted' => (bool) $role];
    }
}
