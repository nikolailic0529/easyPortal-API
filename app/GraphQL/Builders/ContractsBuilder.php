<?php declare(strict_types = 1);

namespace App\GraphQL\Builders;

use App\GraphQL\Queries\ContractTypes;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;

class ContractsBuilder {
    public function __construct(
        protected Repository $config,
        protected ContractTypes $types,
    ) {
        // empty
    }

    public function __invoke(Builder $builder): Builder {
        return $this->types->prepare($builder, 'type_id');
    }
}
