<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Listeners;

use App\Services\DataLoader\Events\DataImported;
use App\Services\Recalculator\Recalculator;
use App\Utils\Providers\EventsProvider;
use Illuminate\Database\Eloquent\Model;

class DataImportedListener implements EventsProvider {
    public function __construct(
        protected Recalculator $recalculator,
    ) {
        // empty
    }

    /**
     * @inheritDoc
     */
    public static function getEvents(): array {
        return [
            DataImported::class,
        ];
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
        $this->recalculator->dispatch([
            'model' => $model,
            'keys'  => $keys,
        ]);
    }
}
