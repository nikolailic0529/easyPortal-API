<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org\Role;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class Builder {
    public function __invoke(Organization $organization): EloquentBuilder {
        return $organization->roles()->getQuery();
    }
}
