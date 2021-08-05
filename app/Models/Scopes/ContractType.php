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
class ContractType implements SearchScope, EloquentScope {
    public function __construct(
        protected Repository $config,
    ) {
        // empty
    }

    public function apply(EloquentBuilder $builder, Model $model): void {
        $key   = $model instanceof Type ? $model->getKeyName() : 'type_id';
        $types = $this->getTypeIds() ?: ['empty'];

        $builder->whereIn($key, $types);
    }

    public function applyForSearch(SearchBuilder $builder, Model $model): void {
        $key   = DocumentType::SEARCH_METADATA;
        $types = $this->getTypeIds() ?: ['empty'];

        $builder->whereMetadataIn($key, $types);
    }

    /**
     * @return array<string>
     */
    public function getTypeIds(): array {
        return (array) $this->config->get('ep.contract_types');
    }

    public function isContractType(Type|string $type): bool {
        $type  = $type instanceof Type ? $type->getKey() : $type;
        $types = $this->getTypeIds();

        return in_array($type, $types, true);
    }
}
