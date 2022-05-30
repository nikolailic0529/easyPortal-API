<?php declare(strict_types = 1);

namespace App\Models\Scopes;

use App\Models\Document;
use App\Services\Search\Builders\Builder as SearchBuilder;
use App\Services\Search\Contracts\ScopeWithMetadata;
use App\Services\Search\Properties\Uuid;
use App\Utils\Eloquent\GlobalScopes\DisableableScope;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends DisableableScope<Document>
 * @implements ScopeWithMetadata<Document>
 */
class DocumentStatusScope extends DisableableScope implements ScopeWithMetadata {
    protected const SEARCH_METADATA = 'statuses';

    public function __construct(
        protected Repository $config,
    ) {
        // empty
    }

    protected function handle(EloquentBuilder $builder, Model $model): void {
        $statuses = $this->getStatusesIds();

        if ($statuses) {
            $key = $builder->getModel()->statuses()->getQualifiedRelatedKeyName();

            $builder->whereDoesntHave('statuses', static function (Builder $builder) use ($key, $statuses): Builder {
                $builder->whereIn($key, $statuses);

                return $builder;
            });
        }
    }

    protected function handleForSearch(SearchBuilder $builder, Model $model): void {
        $key      = self::SEARCH_METADATA;
        $statuses = $this->getStatusesIds();

        if ($statuses) {
            $builder->whereMetadataNotIn($key, $statuses);
        }
    }

    /**
     * @inheritDoc
     */
    public function getSearchMetadata(Model $model): array {
        return [
            self::SEARCH_METADATA => new Uuid('statuses.id'),
        ];
    }

    /**
     * @return array<string>
     */
    protected function getStatusesIds(): array {
        return (array) $this->config->get('ep.document_statuses_hidden');
    }
}
