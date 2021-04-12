<?php declare(strict_types = 1);

namespace App\GraphQL\Builders;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;

class QuotesBuilder {
    public function __construct(
        protected Repository $config,
    ) {
        // empty
    }
    public function __invoke(Builder $builder): Builder {
        $quotesTypes = $this->config->get('ep.quote_types');
        // if empty quotes type we will use ids not represented in contracts
        if (empty($quotesTypes)) {
            return $builder->whereNotIn('type_id', $this->config->get('ep.contract_types'));
        } else {
            return $builder->whereIn('type_id', $quotesTypes);
        }
    }
}
