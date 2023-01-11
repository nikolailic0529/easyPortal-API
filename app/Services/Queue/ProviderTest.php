<?php declare(strict_types = 1);

namespace App\Services\Queue;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

use function array_filter;
use function str_contains;

/**
 * @internal
 * @covers \App\Services\Queue\Provider
 */
class ProviderTest extends TestCase {
    public function testBootSnapshots(): void {
        $expected = 'horizon:snapshot';
        $schedule = $this->app->make(Schedule::class);
        $events   = array_filter($schedule->events(), static function (Event $event) use ($expected): bool {
            return str_contains("{$event->command}", $expected);
        });

        self::assertCount(1, $events, "`{$expected}` is not scheduled.");
    }
}
