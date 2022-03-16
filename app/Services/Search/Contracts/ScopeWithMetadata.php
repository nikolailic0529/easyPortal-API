<?php declare(strict_types = 1);

namespace App\Services\Search\Contracts;

use App\Services\Search\Properties\Property;
use Illuminate\Database\Eloquent\Model;

interface ScopeWithMetadata extends Scope {
    /**
     * Returns properties that must be added to the index as metadata.
     *
     * @return array<string,Property|array<string,Property|array<string,Property|array<string,Property>>>>
     */
    public function getSearchMetadata(Model $model): array;
}
