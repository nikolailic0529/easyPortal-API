<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Contracts;

use App\Models\Document;
use Illuminate\Database\Eloquent\Builder;

class Contracts {
    /**
     * @param Builder<Document> $builder
     */
    public function __invoke(Builder $builder): Builder {
        return $builder->queryContracts();
    }
}
