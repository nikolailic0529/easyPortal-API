<?php declare(strict_types = 1);

namespace App\Models\Scopes;

use App\Models\Data\Status;
use App\Models\Document;
use App\Services\Search\Builders\Builder as SearchBuilder;
use App\Services\Search\Contracts\ScopeWithMetadata;
use App\Services\Search\Properties\Uuid;
use App\Utils\Eloquent\Callbacks\GetKey;
use App\Utils\Eloquent\GlobalScopes\DisableableScope;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

use function array_intersect;

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
        $builder->where('is_hidden', '=', 0);
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

    /**
     * @param Collection<array-key, Status>|Status|string $status
     */
    public function isHidden(Collection|Status|string $status): bool {
        $hidden   = $this->getStatusesIds();
        $statuses = [];

        if ($status instanceof Collection) {
            $statuses = $status->map(new GetKey())->all();
        } elseif ($status instanceof Status) {
            $statuses = [$status->getKey()];
        } else {
            $statuses = [$status];
        }

        return !!array_intersect($statuses, $hidden);
    }
}
