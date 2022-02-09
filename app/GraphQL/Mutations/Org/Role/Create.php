<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org\Role;

use App\Models\Organization;
use App\Models\Role;

class Create {
    public function __construct(
        protected Update $mutation,
    ) {
        // empty
    }

    /**
     * @param array{input: array<mixed>} $args
     */
    public function __invoke(Organization $organization, array $args): Role|bool {
        $input  = new CreateInput($args['input']);
        $role   = $this->create($organization);
        $result = $this->mutation->update($role, $input);

        return $result
            ? $role
            : false;
    }

    protected function create(Organization $organization): Role {
        $role               = new Role();
        $role->organization = $organization;

        return $role;
    }
}
