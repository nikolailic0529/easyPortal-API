<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Listeners;

use App\Events\Subscriber;
use App\Services\DataLoader\Events\DataImported;
use App\Services\Recalculator\Recalculator;
use App\Services\Recalculator\Service;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;

class DataImportedListener implements Subscriber {
    public function __construct(
        protected Service $service,
        protected Recalculator $recalculator,
    ) {
        // empty
    }

    public function subscribe(Dispatcher $dispatcher): void {
        $dispatcher->listen(DataImported::class, $this::class);
    }

    public function __invoke(DataImported $event): void {
        foreach ($event->getData()->getData() as $model => $keys) {
            $this->update($model, $keys);
        }
    }

    /**
     * @param class-string<Model> $model
     * @param array<string|int>   $keys
     */
    private function update(string $model, array $keys): void {
        // Recalculable?
        if (!$this->service->isRecalculableModel($model)) {
            return;
        }

        // Dispatch
        $this->recalculator->dispatch([
            'model' => $model,
            'keys'  => $keys,
        ]);
    }
}
