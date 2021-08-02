<?php declare(strict_types = 1);

namespace App\Models\Scopes;

use App\Models\Type;
use App\Services\Search\Builder as SearchBuilder;
use App\Services\Search\Scope as SearchScope;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope as EloquentScope;

/**
 * @see \App\Models\Type
 * @see \App\Models\Document
 */
class QuoteType implements SearchScope, EloquentScope {
    public function __construct(
        protected Repository $config,
    ) {
        // empty
    }

    public function apply(EloquentBuilder $builder, Model $model): void {
        // if empty quotes type we will use ids not represented in contracts
        $contractTypes = $this->config->get('ep.contract_types');
        $quoteTypes    = $this->config->get('ep.quote_types');
        $key           = $model instanceof Type ? $model->getKeyName() : 'type_id';

        if ($quoteTypes) {
            $builder->whereIn($key, $quoteTypes);
        } elseif ($contractTypes) {
            $builder->whereNotIn($key, $contractTypes);
        } else {
            $builder->whereIn($key, []);
        }
    }

    public function applyForSearch(SearchBuilder $builder, Model $model): void {
        // if empty quotes type we will use ids not represented in contracts
        $contractTypes = $this->config->get('ep.contract_types');
        $quoteTypes    = $this->config->get('ep.quote_types');
        $key           = DocumentType::SEARCH_METADATA;

        if ($quoteTypes) {
            $builder->whereMetadataIn($key, $quoteTypes);
        } elseif ($contractTypes) {
            $builder->whereMetadataNotIn($key, $contractTypes);
        } else {
            $builder->whereMetadataIn($key, []);
        }
    }

    /**
     * @return array<string>
     */
    protected function getTypeIds(): array {
        return (array) $this->config->get('ep.contract_types');
    }
}
