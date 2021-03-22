<?php declare(strict_types = 1);

namespace App\GraphQL\Builders;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;

class TypesBuilder {
    public function __construct(
        protected Repository $config,
    ) {
        // empty
    }
    public function contracts(Builder $builder): Builder {
        $contactTypes = $this->config->get('easyportal.contracts_type_ids');
        return $builder->whereIn('type_id', $contactTypes);
    }

    public function quotes(Builder $builder): Builder {
        $contactTypes = $this->config->get('easyportal.quotes_type_ids');
        return $builder->whereIn('type_id', $contactTypes);
    }
}
