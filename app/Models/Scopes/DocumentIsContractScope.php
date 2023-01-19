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
class DocumentIsContractScope implements SearchScope, EloquentScope {
    public function __construct() {
        // empty
    }

    /**
     * @param EloquentBuilder<Document> $builder
     * @param Document                  $model
     */
    public function apply(EloquentBuilder $builder, Model $model): void {
        $builder->where('is_contract', '=', 1);
    }

    public function applyForSearch(SearchBuilder $builder, Model $model): void {
        $builder->whereMetadata(DocumentIsDocumentScope::SEARCH_METADATA_IS_CONTRACT, true);
    }

    /**
     * @return EloquentBuilder<Type>
     */
    public function getTypeQuery(): EloquentBuilder {
        return Type::query()
            ->where('object_type', '=', (new Document())->getMorphClass())
            ->whereIn((new Type())->getKeyName(), Document::getContractTypeIds())
            ->orderByKey();
    }
}
