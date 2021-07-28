<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use Illuminate\Database\Eloquent\Builder;

class CustomerAssetsAggregate {
    public function __invoke(Builder $builder): Builder {
        // cannot get customer here
        // return $builder->where(`customer_id`, '=', $customer->getKey);
        return $builder;
    }
}
