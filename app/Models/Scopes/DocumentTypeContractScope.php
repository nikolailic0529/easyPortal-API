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
 * @see Document
 *
 * @implements SearchScope<Document>
 */
class DocumentTypeContractScope implements SearchScope, EloquentScope {
    public function __construct(
        protected Repository $config,
    ) {
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
        $builder->whereMetadata(DocumentTypeScope::SEARCH_METADATA_IS_CONTRACT, true);
    }

    /**
     * @return array<string>
     */
    public function getTypeIds(): array {
        return (array) $this->config->get('ep.contract_types');
    }

    /**
     * @return EloquentBuilder<Type>
     */
    public function getTypeQuery(): EloquentBuilder {
        return Type::query()
            ->where('object_type', '=', (new Document())->getMorphClass())
            ->whereIn((new Type())->getKeyName(), $this->getTypeIds())
            ->orderByKey();
    }

    public function isContractType(string|null $type): bool {
        return in_array($type, $this->getTypeIds(), true);
    }
}
