<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Quotes;

use App\Models\Data\Type;
use App\Models\Scopes\DocumentTypeQuoteType;
use Illuminate\Database\Eloquent\Builder;

class QuoteTypes {
    public function __construct(
        protected DocumentTypeQuoteType $scope,
    ) {
        // empty
    }

    /**
     * @return Builder<Type>
     */
    public function __invoke(): Builder {
        return $this->scope->getTypeQuery();
    }
}
