<?php declare(strict_types = 1);

namespace App\GraphQL\Builders;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;

class ContractsBuilder {
    public function __construct(
        protected Repository $config,
    ) {
        // empty
    }
    public function __invoke(Builder $builder): Builder {
        $contactTypes = $this->config->get('ep.contract_types');
        return $builder->whereIn('type_id', $contactTypes);
    }
}
