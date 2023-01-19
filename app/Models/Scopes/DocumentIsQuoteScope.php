<?php declare(strict_types = 1);

namespace App\Models\Scopes;

use App\Models\Data\Type;
use App\Models\Document;
use App\Services\Search\Builders\Builder as SearchBuilder;
use App\Services\Search\Contracts\Scope as SearchScope;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope as EloquentScope;

/**
 * @see Document
 *
 * @implements SearchScope<Document>
 */
class DocumentIsQuoteScope implements SearchScope, EloquentScope {
    public function __construct() {
        // empty
    }

    /**
     * @param EloquentBuilder<Document> $builder
     * @param Document                  $model
     */
    public function apply(EloquentBuilder $builder, Model $model): void {
        $builder->where('is_quote', '=', 1);
    }

    public function applyForSearch(SearchBuilder $builder, Model $model): void {
        $builder->whereMetadata(DocumentIsDocumentScope::SEARCH_METADATA_IS_QUOTE, true);
    }

    /**
     * @return EloquentBuilder<Type>
     */
    public function getTypeQuery(): EloquentBuilder {
        $contractTypes = Document::getContractTypeIds();
        $quoteTypes    = Document::getQuoteTypeIds();
        $query         = Type::query()
            ->where('object_type', '=', (new Document())->getMorphClass())
            ->orderByKey();
        $key           = $query->getModel()->getKeyName();

        if ($quoteTypes) {
            $query->whereIn($key, $quoteTypes);
        } elseif ($contractTypes) {
            $query->whereNotIn($key, $contractTypes);
        } else {
            $query->whereIn($key, ['empty']);
        }

        return $query;
    }
}
