<?php declare(strict_types = 1);

namespace App\Models\Scopes;

use App\Models\Document;
use App\Models\Type;
use App\Services\Search\Builders\Builder as SearchBuilder;
use App\Services\Search\Contracts\ScopeWithMetadata;
use App\Services\Search\Properties\Uuid;
use App\Utils\Eloquent\GlobalScopes\DisableableScope;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;

use function array_merge;

/**
 * @template TModel of \App\Models\Document|\App\Models\Type
 *
 * @extends DisableableScope<TModel>
 * @implements ScopeWithMetadata<TModel>
 */
class DocumentTypeScope extends DisableableScope implements ScopeWithMetadata {
    public const SEARCH_METADATA = 'type';

    /**
     * @param DocumentTypeContractScope<Document> $contractType
     * @param DocumentTypeQuoteType<Document>     $quoteType
     */
    public function __construct(
        private DocumentTypeContractScope $contractType,
        private DocumentTypeQuoteType $quoteType,
    ) {
        // empty
    }

    protected function handle(EloquentBuilder $builder, Model $model): void {
        $contractTypes = $this->contractType->getTypeIds();
        $quoteTypes    = $this->quoteType->getTypeIds();
        $key           = $model instanceof Type ? $model->getKeyName() : 'type_id';

        if ($contractTypes && $quoteTypes) {
            $builder->whereIn($key, array_merge($contractTypes, $quoteTypes));
        }
    }

    protected function handleForSearch(SearchBuilder $builder, Model $model): void {
        $contractTypes = $this->contractType->getTypeIds();
        $quoteTypes    = $this->quoteType->getTypeIds();

        if ($contractTypes && $quoteTypes) {
            $builder->whereMetadataIn(self::SEARCH_METADATA, array_merge($contractTypes, $quoteTypes));
        }
    }

    /**
     * @inheritDoc
     */
    public function getSearchMetadata(Model $model): array {
        return [
            self::SEARCH_METADATA => new Uuid('type_id'),
        ];
    }
}
