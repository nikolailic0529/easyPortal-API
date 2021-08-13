<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Quotes;

use App\Models\Scopes\QuoteType;
use App\Services\Search\Builders\Builder;

class QuotesSearch {
    public function __invoke(Builder $builder): Builder {
        return $builder->applyScope(QuoteType::class);
    }
}
