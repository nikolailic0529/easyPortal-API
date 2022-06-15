<?php declare(strict_types = 1);

namespace App\Services\Search\Queue\Tasks;

use App\Services\Search\Eloquent\Searchable;
use App\Utils\Eloquent\Model;
use Illuminate\Contracts\Queue\ShouldBeUnique;

use function implode;

/**
 * Update Search Index for one Model.
 *
 * @see \Laravel\Scout\Jobs\MakeSearchable
 * @see \Laravel\Scout\Jobs\RemoveFromSearch
 */
class ModelIndex extends Index implements ShouldBeUnique {
    public function displayName(): string {
        return 'ep-search-model-index';
    }

    public function uniqueId(): string {
        return "{$this->getModel()}@".implode(',', $this->getKeys());
    }

    /**
     * @param class-string<Model&Searchable> $model
     *
     * @return $this
     */
    public function init(string $model, string|int $key): static {
        $this->setModel($model);
        $this->setKeys([$key]);
        $this->initialized();

        return $this;
    }
}
