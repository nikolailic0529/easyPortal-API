<?php declare(strict_types = 1);

namespace App\GraphQL\Builders;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;

class ContractTypesBuilder {
    public function __construct(
        protected Repository $config,
    ) {
        // empty
    }
    public function __invoke(Builder $builder): Builder {
        $contactTypes = $this->config->get('easyportal.contracts_type_ids');
        // if empty should not be used
        return empty($contactTypes) ? $builder : $builder->whereIn('type_id', $contactTypes);
    }
}
