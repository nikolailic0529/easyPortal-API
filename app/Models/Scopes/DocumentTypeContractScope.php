<?php declare(strict_types = 1);

namespace App\Models\Scopes;

use App\Models\Type;
use App\Services\Search\Builders\Builder as SearchBuilder;
use App\Services\Search\Contracts\Scope as SearchScope;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope as EloquentScope;

use function in_array;

/**
 * @see \App\Models\Type
 * @see \App\Models\Document
 *
 * @template TModel of \App\Models\Document|\App\Models\Type
 *
 * @implements SearchScope<TModel>
 */
class DocumentTypeContractScope implements SearchScope, EloquentScope {
    public function __construct(
        protected Repository $config,
    ) {
        // empty
    }

    /**
     * @param EloquentBuilder<TModel> $builder
     * @param TModel                  $model
     */
    public function apply(EloquentBuilder $builder, Model $model): void {
        $key   = $model instanceof Type ? $model->getKeyName() : 'type_id';
        $types = $this->getTypeIds() ?: ['empty'];

        $builder->whereIn($key, $types);
    }

    public function applyForSearch(SearchBuilder $builder, Model $model): void {
        $key   = DocumentTypeScope::SEARCH_METADATA;
        $types = $this->getTypeIds() ?: ['empty'];

        $builder->whereMetadataIn($key, $types);
    }

    /**
     * @return array<string>
     */
    public function getTypeIds(): array {
        return (array) $this->config->get('ep.contract_types');
    }

    public function isContractType(Type|string|null $type): bool {
        $type  = $type instanceof Type ? $type->getKey() : $type;
        $types = $this->getTypeIds();

        return in_array($type, $types, true);
    }
}
