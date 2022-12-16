<?php declare(strict_types = 1);

namespace App\Services\Recalculator;

use App\Services\Queue\Utils\Dispatcher;
use App\Services\Recalculator\Queue\Tasks\ModelRecalculate;
use App\Services\Recalculator\Queue\Tasks\ModelsRecalculate;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Dispatcher<Model>
 */
class Recalculator extends Dispatcher {
    public function __construct(
        protected Service $service,
    ) {
        parent::__construct();
    }

    protected function isDispatchable(string $model): bool {
        return parent::isDispatchable($model)
            && $this->service->isRecalculableModel($model);
    }

    protected function dispatchModel(string $model, int|string $key): void {
        Container::getInstance()->make(ModelRecalculate::class)
            ->init($model, $key)
            ->dispatch();
    }

    /**
     * @inheritDoc
     */
    protected function dispatchModels(string $model, array $keys): void {
        Container::getInstance()->make(ModelsRecalculate::class)
            ->init($model, $keys)
            ->dispatch();
    }
}
