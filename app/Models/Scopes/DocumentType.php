<?php declare(strict_types = 1);

namespace App\Models\Scopes;

use App\Services\Search\Builders\Builder as SearchBuilder;
use App\Services\Search\Contracts\ScopeWithMetadata;
use App\Services\Search\Properties\Uuid;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * @template TModel of \App\Models\Document|\App\Models\Type
 *
 * @implements ScopeWithMetadata<TModel>
 */
class DocumentType implements Scope, ScopeWithMetadata {
    public const SEARCH_METADATA = 'type';

    public function __construct() {
        // empty
    }

    /**
     * @param EloquentBuilder<TModel> $builder
     * @param TModel                  $model
     */
    public function apply(EloquentBuilder $builder, Model $model): void {
        // empty
    }

    /**
     * @param TModel $model
     */
    public function applyForSearch(SearchBuilder $builder, Model $model): void {
        // empty
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
