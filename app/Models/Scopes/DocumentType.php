<?php declare(strict_types = 1);

namespace App\Models\Scopes;

use App\Services\Search\Builder as SearchBuilder;
use App\Services\Search\ScopeWithMetadata;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

use function array_filter;
use function array_merge;
use function array_unique;

class DocumentType implements Scope, ScopeWithMetadata {
    public const SEARCH_METADATA = 'type';

    public function __construct(
        protected ContractType $contractType,
        protected QuoteType $quoteType,
    ) {
        // empty
    }

    public function apply(EloquentBuilder $builder, Model $model): void {
        // empty
    }

    public function applyForSearch(SearchBuilder $builder, Model $model): void {
        $builder->whereMetadataIn(static::SEARCH_METADATA, array_filter(array_unique(array_merge(
            (array) $this->contractType->getTypeIds(),
            (array) $this->quoteType->getTypeIds(),
        ))));
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
