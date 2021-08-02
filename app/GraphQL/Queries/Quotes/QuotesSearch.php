<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Quotes;

use App\Models\Document;
use App\Models\Scopes\QuoteType;
use Illuminate\Database\Eloquent\Builder;

class QuotesSearch {
    public function __construct(
        protected QuoteType $scope,
    ) {
        // empty
    }

    public function __invoke(): Builder {
        Document::addGlobalScope($this->scope);

        return Document::query();
    }
}
