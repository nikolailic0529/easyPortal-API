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
 */
class ContractType implements SearchScope, EloquentScope {
    public function __construct(
        protected Repository $config,
    ) {
        // empty
    }

    public function apply(EloquentBuilder $builder, Model $model): void {
        $key = $model instanceof Type ? $model->getKeyName() : 'type_id';

        $builder->whereIn($key, $this->getTypeIds());
    }

    public function applyForSearch(SearchBuilder $builder, Model $model): void {
        $builder->whereMetadata(DocumentTypeScope::SEARCH_METADATA, $this->getTypeIds());
    }

    /**
     * @return array<string>
     */
    protected function getTypeIds(): array {
        return (array) $this->config->get('ep.contract_types');
    }
}
