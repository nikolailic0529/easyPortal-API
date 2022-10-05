<?php declare(strict_types = 1);

namespace App\Services\Recalculator;

use App\Services\Queue\Utils\Dispatcher;
use App\Services\Recalculator\Queue\Tasks\ModelRecalculate;
use App\Services\Recalculator\Queue\Tasks\ModelsRecalculate;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Dispatcher<Model>
 */
class Recalculator extends Dispatcher {
    public function __construct(
        Container $container,
        protected Service $service,
    ) {
        parent::__construct($container);
    }

    protected function isDispatchable(string $model): bool {
        return parent::isDispatchable($model)
            && $this->service->isRecalculableModel($model);
    }

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
