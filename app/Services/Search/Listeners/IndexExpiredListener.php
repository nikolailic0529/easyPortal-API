<?php declare(strict_types = 1);

namespace App\Services\Search\Listeners;

use App\Events\Subscriber;
use App\Services\DataLoader\Events\DataImported;
use App\Services\Recalculator\Events\ModelsRecalculated;
use App\Services\Search\Jobs\UpdateIndexJob;
use App\Services\Search\Service;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;

use function array_values;

class IndexExpiredListener implements Subscriber {
    public function __construct(
        protected Container $container,
        protected Service $service,
    ) {
        // empty
    }

    public function subscribe(Dispatcher $dispatcher): void {
        $dispatcher->listen(DataImported::class, $this::class);
        $dispatcher->listen(ModelsRecalculated::class, $this::class);
    }

    public function __invoke(DataImported|ModelsRecalculated $event): void {
        if ($event instanceof ModelsRecalculated) {
            $this->update($event->getModel(), $event->getKeys());
        } else {
            foreach ($event->getData()->getData() as $model => $keys) {
                $this->update($model, $keys);
            }
        }
    }

    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model> $model
     * @param array<string>                                     $keys
     */
    private function update(string $model, array $keys): void {
        // Searchable?
        if (!$this->service->isSearchableModel($model)) {
            return;
        }

        // Dispatch
        $keys = array_values($keys);

        if ($keys) {
            $this->container->make(UpdateIndexJob::class)
                ->setModels($model, $keys)
                ->dispatch();
        }
    }
}
