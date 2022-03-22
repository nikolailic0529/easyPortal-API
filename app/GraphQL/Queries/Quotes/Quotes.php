<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Quotes;

use App\Models\Document;
use Illuminate\Database\Eloquent\Builder;

class Quotes {
    /**
     * @param Builder|Document $builder
     */
    public function __invoke(Builder $builder): Builder {
        return $builder->queryQuotes();
    }
}
