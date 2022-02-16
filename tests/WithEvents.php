<?php declare(strict_types = 1);

namespace Tests;

use App\Services\DataLoader\Events\DataImported;
use Illuminate\Support\Collection;
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
     * @param array<object>|\Illuminate\Support\Collection<object> $events
     */
    protected function assertDispatchedEventsEquals(
        string $expected,
        array|Collection $events,
        string $message = '',
    ): void {
        $data    = $this->getTestData();
        $events  = (new Collection($events))->map(fn(array $event) => $this->cleanupEvent(reset($event)));
        $actual  = json_encode($events, JSON_PRETTY_PRINT | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
        $content = trim($data->content($expected));

        if ($content === '') {
            self::assertNotFalse(file_put_contents($data->path($expected), "{$actual}\n"));
        } else {
            self::assertEquals($content, $actual, $message);
        }
    }

    private function cleanupEvent(object $event): mixed {
        $data = null;

        if ($event instanceof DataImported) {
            $data = $event->getData()->getData();
        } else {
            throw new LogicException('Not yet supported.');
        }

        return $data;
    }
}