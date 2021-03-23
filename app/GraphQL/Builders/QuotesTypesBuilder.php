<?php declare(strict_types = 1);

namespace App\GraphQL\Builders;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;

class QuotesTypesBuilder {
    public function __construct(
        protected Repository $config,
    ) {
        // empty
    }
    public function __invoke(Builder $builder): Builder {
        $quotesTypes = $this->config->get('easyportal.quotes_type_ids');
        // if empty should not be used
        return empty($quotesTypes) ? $builder : $builder->whereIn('type_id', $quotesTypes);
    }
}
