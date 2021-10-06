<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Contracts;

use Illuminate\Database\Eloquent\Builder;

class Contracts {
    /**
     * @param \Illuminate\Database\Eloquent\Builder|\App\Models\Document $builder
     */
    public function __invoke(Builder $builder): Builder {
        return $builder->queryContracts();
    }
}
