<?php declare(strict_types = 1);

namespace Tests;

use App\Services\DataLoader\Events\DataImported;
use App\Services\Recalculator\Events\ModelsRecalculated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Laravel\Scout\Events\ModelsImported;
use LogicException;

use function file_put_contents;
use function json_encode;
use function reset;
use function trim;

use const JSON_PRESERVE_ZERO_FRACTION;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;

trait WithEvents {
    /**
     * @param array<object>|Collection<int, object> $events
     */
    protected function assertDispatchedEventsEquals(
        string $expected,
        array|Collection $events,
        string $message = '',
    ): void {
        $data    = $this->getTestData();
        $events  = (new Collection($events))->map(fn(array $event) => $this->cleanupEvent(reset($event)));
        $actual  = json_encode($events, JSON_PRETTY_PRINT | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
        $content = $data->file($expected)->isFile()
            ? trim($data->content($expected))
            : null;

        if (!$content) {
            self::assertNotFalse(file_put_contents($data->path($expected), "{$actual}\n"));
        } else {
            self::assertEquals($content, $actual, $message);
        }
    }

    private function cleanupEvent(object $event): mixed {
        // todo(tests): Probably will be good to add some generic interface for
        //      all app's events and for events that can be dumped/compared.
        $class = $event::class;
        $data  = null;

        if ($event instanceof DataImported) {
            $data = $event->getData()->getData();
        } elseif ($event instanceof ModelsRecalculated) {
            $data = [
                'model' => $event->getModel(),
                'keys'  => $event->getKeys(),
            ];
        } elseif ($event instanceof ModelsImported) {
            $data = [
                'models' => $event->models->map(static function (Model $model): mixed {
                    return $model->getKey();
                }),
            ];
        } else {
            throw new LogicException('Not yet supported.');
        }

        return [
            'event' => $class,
            'data'  => $data,
        ];
    }
}
