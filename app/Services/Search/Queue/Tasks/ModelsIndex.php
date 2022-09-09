<?php declare(strict_types = 1);

namespace App\Services\Search\Queue\Tasks;

use App\Services\Queue\Concerns\WithModelKeys;
use App\Services\Search\Eloquent\Searchable;
use Illuminate\Database\Eloquent\Model;

/**
 * Adds models into Search Index.
 */
class ModelsIndex extends Index {
    /**
     * @use WithModelKeys<Model&Searchable>
     */
    use WithModelKeys;

    public function displayName(): string {
        return 'ep-search-models-index';
    }
}
