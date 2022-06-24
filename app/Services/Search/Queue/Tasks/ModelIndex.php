<?php declare(strict_types = 1);

namespace App\Services\Search\Queue\Tasks;

use App\Services\Queue\Concerns\WithModelKey;
use Illuminate\Contracts\Queue\ShouldBeUnique;

/**
 * Update Search Index for one Model.
 *
 * @see \Laravel\Scout\Jobs\MakeSearchable
 * @see \Laravel\Scout\Jobs\RemoveFromSearch
 */
class ModelIndex extends Index implements ShouldBeUnique {
    /**
     * @use WithModelKey<\Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable>
     */
    use WithModelKey;

    public function displayName(): string {
        return 'ep-search-model-index';
    }

    /**
     * @inheritDoc
     */
    protected function getKeys(): array {
        return [$this->getKey()];
    }
}
