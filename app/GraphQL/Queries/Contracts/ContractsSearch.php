<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Contracts;

use App\Models\Document;
use App\Models\Scopes\DocumentIsContractScope;
use App\Services\Search\Builders\Builder;

class ContractsSearch {
    /**
     * @param Builder<Document> $builder
     *
     * @return Builder<Document>
     */
    public function __invoke(Builder $builder): Builder {
        return $builder->applyScope(DocumentIsContractScope::class);
    }
}
