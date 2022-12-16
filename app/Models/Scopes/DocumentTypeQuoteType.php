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

use function in_array;

/**
 * @see Type
 * @see Document
 *
 * @template TModel of Document|Type
 *
 * @implements SearchScope<TModel>
 */
class DocumentTypeQuoteType implements SearchScope, EloquentScope {
    /**
     * @param DocumentTypeContractScope<TModel> $contractType
     */
    public function __construct(
        protected Repository $config,
        protected DocumentTypeContractScope $contractType,
    ) {
        // empty
    }

    /**
     * @param EloquentBuilder<TModel> $builder
     * @param TModel                  $model
     */
    public function apply(EloquentBuilder $builder, Model $model): void {
        // if empty quotes type we will use ids not represented in contracts
        $contractTypes = $this->contractType->getTypeIds();
        $quoteTypes    = $this->getTypeIds();
        $key           = $model instanceof Type ? $model->getKeyName() : 'type_id';

        if ($quoteTypes) {
            $builder->whereIn($key, $quoteTypes);
        } elseif ($contractTypes) {
            $builder->whereNotIn($key, $contractTypes);
        } else {
            $builder->whereIn($key, ['empty']);
        }
    }

    public function applyForSearch(SearchBuilder $builder, Model $model): void {
        // if empty quotes type we will use ids not represented in contracts
        $contractTypes = $this->contractType->getTypeIds();
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
        return (array) $this->config->get('ep.quote_types');
    }

    public function isQuoteType(Type|string|null $type): bool {
        $contractTypes = $this->contractType->getTypeIds();
        $quoteTypes    = $this->getTypeIds();
        $type          = $type instanceof Type ? $type->getKey() : $type;
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
