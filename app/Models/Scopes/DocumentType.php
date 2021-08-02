<?php declare(strict_types = 1);

namespace App\Models\Scopes;

use App\Services\Search\Builder as SearchBuilder;
use App\Services\Search\ScopeWithMetadata;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class DocumentType implements Scope, ScopeWithMetadata {
    public const SEARCH_METADATA = 'type';

    public function applyForSearch(SearchBuilder $builder, Model $model): void {
        // empty
    }

    public function apply(EloquentBuilder $builder, Model $model): void {
        // empty
    }

    /**
     * @inheritDoc
     */
    public function getSearchMetadata(Model $model): array {
        return [
            static::SEARCH_METADATA => 'type_id',
        ];
    }
}
