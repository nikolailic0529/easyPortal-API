<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Contracts;

use App\Models\Document;
use App\Models\Scopes\ContractType;
use Illuminate\Database\Eloquent\Builder;

class ContractsSearch {
    public function __construct(
        protected ContractType $scope,
    ) {
        // empty
    }

    public function __invoke(): Builder {
        Document::addGlobalScope($this->scope);

        return Document::query();
    }
}
