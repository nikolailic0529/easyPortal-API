<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Documents;

use App\Models\Document;
use Illuminate\Database\Eloquent\Builder;

class Documents {
    /**
     * @param Builder|Document $builder
     */
    public function __invoke(Builder $builder): Builder {
        return $builder->queryDocuments();
    }
}
