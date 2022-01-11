<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Administration;

use Illuminate\Database\Eloquent\Builder;

class Roles {
    public function __construct() {
        // empty
    }

    public function __invoke(Builder $builder): Builder {
        return $builder->whereNull('organization_id');
    }
}
