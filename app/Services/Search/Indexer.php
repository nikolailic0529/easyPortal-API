<?php declare(strict_types = 1);

namespace App\Services\Search;

use App\Services\Queue\Utils\Dispatcher;
use App\Services\Search\Eloquent\Searchable;
use App\Services\Search\Queue\Tasks\ModelIndex;
use App\Services\Search\Queue\Tasks\ModelsIndex;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Dispatcher<Model&Searchable>
 */
class Indexer extends Dispatcher {
    protected function dispatchModel(string $model, int|string $key): void {
        $this->getContainer()->make(ModelIndex::class)
            ->init($model, $key)
            ->dispatch();
    }

    /**
     * @inheritDoc
     */
    protected function dispatchModels(string $model, array $keys): void {
        $this->getContainer()->make(ModelsIndex::class)
            ->init($model, $keys)
            ->dispatch();
    }
}
