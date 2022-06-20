<?php declare(strict_types = 1);

namespace App\Services\Search\Queue\Tasks;

use App\Services\Queue\Concerns\WithModelKeys;

/**
 * Adds models into Search Index.
 */
class ModelsIndex extends Index {
    /**
     * @use WithModelKeys<\Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable>
     */
    use WithModelKeys;

    public function displayName(): string {
        return 'ep-search-models-index';
    }
}
