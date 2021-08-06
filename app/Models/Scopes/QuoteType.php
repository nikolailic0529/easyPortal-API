<?php declare(strict_types = 1);

namespace App\Models\Scopes;

use App\Models\Type;
use App\Services\Search\Builder as SearchBuilder;
use App\Services\Search\Scope as SearchScope;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope as EloquentScope;

use function in_array;

/**
 * @see \App\Models\Type
 * @see \App\Models\Document
 */
class QuoteType implements SearchScope, EloquentScope {
    public function __construct(
        protected Repository $config,
        protected ContractType $contractType,
    ) {
        // empty
    }

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
        $key           = DocumentType::SEARCH_METADATA;

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

    public function isQuoteType(Type|string $type): bool {
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