<?php declare(strict_types = 1);

namespace App\Services\Search\Contracts;

use App\Services\Search\Properties\Property;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends Scope<TModel>
 */
interface ScopeWithMetadata extends Scope {
    /**
     * Returns properties that must be added to the index as metadata.
     *
     * @param TModel $model
     *
     * @return array<string,Property>
     */
    public function getSearchMetadata(Model $model): array;
}
