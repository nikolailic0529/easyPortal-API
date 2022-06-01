<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Quotes;

use App\Models\Document;
use App\Models\Scopes\DocumentTypeQuoteType;
use App\Services\Search\Builders\Builder;

class QuotesSearch {
    /**
     * @param Builder<Document> $builder
     *
     * @return Builder<Document>
     */
    public function __invoke(Builder $builder): Builder {
        return $builder->applyScope(DocumentTypeQuoteType::class);
    }
}
