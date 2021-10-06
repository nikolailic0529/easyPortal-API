<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Quotes;

use Illuminate\Database\Eloquent\Builder;

class Quotes {
    /**
     * @param \Illuminate\Database\Eloquent\Builder|\App\Models\Document $builder
     */
    public function __invoke(Builder $builder): Builder {
        return $builder->queryQuotes();
    }
}
