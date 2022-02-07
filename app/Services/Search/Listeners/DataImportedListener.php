<?php declare(strict_types = 1);

namespace App\Services\Search\Listeners;

use App\Events\Subscriber;
use App\Services\DataLoader\Events\DataImported;
use App\Services\Search\Jobs\UpdateIndexJob;
use App\Services\Search\Service;
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
        $models = $this->service->getSearchableModels();
        $data   = $event->getData();

        foreach ($models as $model) {
            $ids = $data->get($model);

            if ($ids) {
                $this->container->make(UpdateIndexJob::class)
                    ->setModels($model, array_values($ids))
                    ->dispatch();
            }
        }
    }
}
