<?php declare(strict_types = 1);

namespace App\Services\Search\Queue\Tasks;

use App\Services\Queue\Concerns\WithModelKey;
use App\Services\Search\Eloquent\Searchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Database\Eloquent\Model;

/**
 * Update Search Index for one Model.
 *
 * @see \Laravel\Scout\Jobs\MakeSearchable
 * @see \Laravel\Scout\Jobs\RemoveFromSearch
 */
class ModelIndex extends Index implements ShouldBeUnique {
    /**
     * @use WithModelKey<Model&Searchable>
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
