<?php declare(strict_types = 1);

namespace App\Services\Recalculator;

use App\Services\Queue\Utils\Dispatcher;
use App\Services\Recalculator\Queue\Tasks\ModelRecalculate;
use App\Services\Recalculator\Queue\Tasks\ModelsRecalculate;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Dispatcher<Model>
 */
class Recalculator extends Dispatcher {
    protected function dispatchModel(string $model, int|string $key): void {
        $this->getContainer()->make(ModelRecalculate::class)
            ->init($model, $key)
            ->dispatch();
    }

    /**
     * @inheritDoc
     */
    protected function dispatchModels(string $model, array $keys): void {
        $this->getContainer()->make(ModelsRecalculate::class)
            ->init($model, $keys)
            ->dispatch();
    }
}
