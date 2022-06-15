<?php declare(strict_types = 1);

namespace App\Services\Search\Queue\Tasks;

use App\Services\Search\Eloquent\Searchable;
use Illuminate\Database\Eloquent\Model;

/**
 * Adds models into Search Index.
 */
class ModelsIndex extends Index {
    public function displayName(): string {
        return 'ep-search-models-index';
    }

    /**
     * @param class-string<Model&Searchable> $model
     * @param array<string|int>              $keys
     *
     * @return $this
     */
    public function init(string $model, array $keys): static {
        $this->setModel($model);
        $this->setKeys($keys);
        $this->initialized();

        return $this;
    }
}
