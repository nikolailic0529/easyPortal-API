<?php declare(strict_types = 1);

namespace App\Models\Scopes;

use App\Models\Document;
use App\Services\Search\Builders\Builder as SearchBuilder;
use App\Services\Search\Contracts\ScopeWithMetadata;
use App\Services\Search\Properties\Uuid;
use App\Utils\Eloquent\GlobalScopes\DisableableScope;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;

use function array_merge;

/**
 * @extends DisableableScope<Document>
 * @implements ScopeWithMetadata<Document>
 */
class DocumentTypeScope extends DisableableScope implements ScopeWithMetadata {
    public const SEARCH_METADATA = 'type';

    public function __construct(
        private DocumentTypeContractScope $contractType,
        private DocumentTypeQuoteType $quoteType,
    ) {
        // empty
    }

    protected function handle(EloquentBuilder $builder, Model $model): void {
        $builder->where(function (EloquentBuilder $builder) use ($model): void {
            $builder->orWhere(function (EloquentBuilder $builder) use ($model): void {
                $this->contractType->apply($builder, $model);
            });
            $builder->orWhere(function (EloquentBuilder $builder) use ($model): void {
                $this->quoteType->apply($builder, $model);
            });
        });
    }

    protected function handleForSearch(SearchBuilder $builder, Model $model): void {
        $contractTypes = $this->contractType->getTypeIds();
        $quoteTypes    = $this->quoteType->getTypeIds();

        if ($contractTypes && $quoteTypes) {
            $builder->whereMetadataIn(self::SEARCH_METADATA, array_merge($contractTypes, $quoteTypes));
        }
    }

    /**
     * @inheritDoc
     */
    public function getSearchMetadata(Model $model): array {
        return [
            self::SEARCH_METADATA => new Uuid('type_id'),
        ];
    }
}
