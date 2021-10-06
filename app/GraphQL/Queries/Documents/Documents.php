<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Documents;

use Illuminate\Database\Eloquent\Builder;

class Documents {
    /**
     * @param \Illuminate\Database\Eloquent\Builder|\App\Models\Document $builder
     */
    public function __invoke(Builder $builder): Builder {
        return $builder->queryDocuments();
    }
}
