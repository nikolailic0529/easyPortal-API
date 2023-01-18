<?php declare(strict_types = 1);

namespace App\Models\Scopes;

use App\Models\Document;
use App\Services\Search\Builders\Builder as SearchBuilder;
use App\Services\Search\Contracts\ScopeWithMetadata;
use App\Services\Search\Properties\Boolean;
use App\Utils\Eloquent\GlobalScopes\DisableableScope;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends DisableableScope<Document>
 * @implements ScopeWithMetadata<Document>
 */
class DocumentTypeScope extends DisableableScope implements ScopeWithMetadata {
    public const SEARCH_METADATA_IS_DOCUMENT = 'is_document';
    public const SEARCH_METADATA_IS_CONTRACT = 'is_contract';
    public const SEARCH_METADATA_IS_QUOTE    = 'is_quote';

    public function __construct(
        private DocumentTypeContractScope $contractType,
        private DocumentTypeQuoteType $quoteType,
    ) {
        // empty
    }

    protected function handle(EloquentBuilder $builder, Model $model): void {
        $builder->where(function (EloquentBuilder $builder) use ($model): void {
            $builder->orWhere(function (EloquentBuilder $builder) use ($model): void {
                $this->contractType->apply($builder, $model);
            });
            $builder->orWhere(function (EloquentBuilder $builder) use ($model): void {
                $this->quoteType->apply($builder, $model);
            });
        });
    }

    protected function handleForSearch(SearchBuilder $builder, Model $model): void {
        $builder->whereMetadata(self::SEARCH_METADATA_IS_DOCUMENT, true);
    }

    /**
     * @inheritDoc
     */
    public function getSearchMetadata(Model $model): array {
        return [
            self::SEARCH_METADATA_IS_DOCUMENT => new Boolean('is_document'),
            self::SEARCH_METADATA_IS_CONTRACT => new Boolean('is_contract'),
            self::SEARCH_METADATA_IS_QUOTE    => new Boolean('is_quote'),
        ];
    }
}
