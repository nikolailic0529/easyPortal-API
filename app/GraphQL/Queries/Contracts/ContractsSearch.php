<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Contracts;

use App\Models\Scopes\ContractType;
use App\Services\Search\Builders\Builder;

class ContractsSearch {
    public function __invoke(Builder $builder): Builder {
        return $builder->applyScope(ContractType::class);
    }
}
