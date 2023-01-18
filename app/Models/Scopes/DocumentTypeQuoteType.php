<?php declare(strict_types = 1);

namespace App\Models\Scopes;

use App\Models\Data\Type;
use App\Models\Document;
use App\Services\Search\Builders\Builder as SearchBuilder;
use App\Services\Search\Contracts\Scope as SearchScope;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope as EloquentScope;

use function array_diff;
use function in_array;

/**
 * @see Document
 *
 * @implements SearchScope<Document>
 */
class DocumentTypeQuoteType implements SearchScope, EloquentScope {
    public function __construct(
        protected Repository $config,
        protected DocumentTypeContractScope $contractScope,
    ) {
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
        // if empty quotes type we will use ids not represented in contracts
        $contractTypes = $this->contractScope->getTypeIds();
        $quoteTypes    = $this->getTypeIds();
        $key           = DocumentTypeScope::SEARCH_METADATA;

        if ($quoteTypes) {
            $builder->whereMetadataIn($key, $quoteTypes);
        } elseif ($contractTypes) {
            $builder->whereMetadataNotIn($key, $contractTypes);
        } else {
            $builder->whereMetadataIn($key, ['empty']);
        }
    }

    /**
     * @return array<string>
     */
    public function getTypeIds(): array {
        $quoteTypes    = (array) $this->config->get('ep.quote_types');
        $contractTypes = $this->contractScope->getTypeIds();

        if ($contractTypes) {
            $quoteTypes = array_diff($quoteTypes, $contractTypes);
        }

        return $quoteTypes;
    }

    /**
     * @return EloquentBuilder<Type>
     */
    public function getTypeQuery(): EloquentBuilder {
        $contractTypes = $this->contractScope->getTypeIds();
        $quoteTypes    = $this->getTypeIds();
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

    public function isQuoteType(string|null $type): bool {
        $contractTypes = $this->contractScope->getTypeIds();
        $quoteTypes    = $this->getTypeIds();
        $is            = false;

        if ($quoteTypes) {
            $is = in_array($type, $quoteTypes, true);
        } elseif ($contractTypes) {
            $is = !in_array($type, $contractTypes, true);
        } else {
            // empty
        }

        return $is;
    }
}
