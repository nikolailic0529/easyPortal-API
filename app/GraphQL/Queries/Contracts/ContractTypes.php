<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Contracts;

use App\Models\Data\Type;
use App\Models\Scopes\DocumentTypeContractScope;
use Illuminate\Database\Eloquent\Builder;

class ContractTypes {
    public function __construct(
        protected DocumentTypeContractScope $scope,
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
