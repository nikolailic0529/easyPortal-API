<?php declare(strict_types = 1);

namespace App\Models\Scopes;

use App\Models\Document;
use App\Services\Search\Builders\Builder as SearchBuilder;
use App\Services\Search\Contracts\ScopeWithMetadata;
use App\Services\Search\Properties\Boolean;
use App\Utils\Eloquent\GlobalScopes\DisableableScope;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends DisableableScope<Document>
 * @implements ScopeWithMetadata<Document>
 */
class DocumentIsHiddenScope extends DisableableScope implements ScopeWithMetadata {
    protected const SEARCH_METADATA = 'is_hidden';

    public function __construct() {
        // empty
    }

    protected function handle(EloquentBuilder $builder, Model $model): void {
        $builder->where('is_hidden', '=', 0);
    }

    protected function handleForSearch(SearchBuilder $builder, Model $model): void {
        $builder->whereMetadata(self::SEARCH_METADATA, false);
    }

    /**
     * @inheritDoc
     */
    public function getSearchMetadata(Model $model): array {
        return [
            self::SEARCH_METADATA => new Boolean('is_hidden'),
        ];
    }
}
