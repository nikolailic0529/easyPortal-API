<?php declare(strict_types = 1);

namespace App\Services\Search\Listeners;

use App\Services\DataLoader\Events\DataImported;
use App\Services\Recalculator\Events\ModelsRecalculated;
use App\Services\Search\Indexer;
use App\Services\Search\Service;
use App\Utils\Providers\EventsProvider;
use Illuminate\Database\Eloquent\Model;

class IndexExpiredListener implements EventsProvider {
    public function __construct(
        protected Service $service,
        protected Indexer $indexer,
    ) {
        // empty
    }

    /**
     * @inheritDoc
     */
    public static function getEvents(): array {
        return [
            DataImported::class,
            ModelsRecalculated::class,
        ];
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
     * @param class-string<Model> $model
     * @param array<string|int>   $keys
     */
    private function update(string $model, array $keys): void {
        // Searchable?
        if (!$this->service->isSearchableModel($model)) {
            return;
        }

        // Dispatch
        $this->indexer->dispatch([
            'model' => $model,
            'keys'  => $keys,
        ]);
    }
}
