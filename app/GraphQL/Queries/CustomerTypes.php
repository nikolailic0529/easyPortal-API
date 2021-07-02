<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;

class CustomerTypes {
    public function __invoke(Builder $builder): Builder {
        return $builder->where('object_type', '=', (new Customer())->getMorphClass());
    }
}
