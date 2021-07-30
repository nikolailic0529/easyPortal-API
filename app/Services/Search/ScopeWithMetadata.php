<?php declare(strict_types = 1);

namespace App\Services\Search;

use Illuminate\Database\Eloquent\Model;

interface ScopeWithMetadata extends Scope {
    /**
     * Returns properties that must be added to the index as metadata.
     *
     * @return array<string,string|array<string,string|array<string,string|array<string,string>>>>
     */
    public function getSearchMetadata(Model $model): array;
}
