<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Listeners;

use App\Events\Subscriber;
use App\Services\DataLoader\Events\DataImported;
use App\Services\Recalculator\Service;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;

use function array_values;

class DataImportedListener implements Subscriber {
    public function __construct(
        protected Container $container,
        protected Service $service,
    ) {
        // empty
    }

    public function subscribe(Dispatcher $dispatcher): void {
        $dispatcher->listen(DataImported::class, $this::class);
    }

    public function __invoke(DataImported $event): void {
        $models = $this->service->getRecalculableModels();
        $data   = $event->getData();

        foreach ($models as $model) {
            $job = $this->service->getRecalculableModelJob($model);
            $ids = $data->get($model);

            if ($job && $ids) {
                $this->container->make($job)
                    ->setModels(array_values($ids))
                    ->dispatch();
            }
        }
    }
}
