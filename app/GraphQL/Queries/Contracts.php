<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use Illuminate\Database\Eloquent\Builder;

class Contracts {
    public function __construct(
        protected ContractTypes $types,
    ) {
        // empty
    }

    public function __invoke(Builder $builder): Builder {
        return $this->types->prepare($builder);
    }
}
