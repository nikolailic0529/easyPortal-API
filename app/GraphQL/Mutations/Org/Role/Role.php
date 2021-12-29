<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org\Role;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;

class Role {
    public function __invoke(Organization $organization): Builder {
        return $organization->roles()->getQuery();
    }
}
