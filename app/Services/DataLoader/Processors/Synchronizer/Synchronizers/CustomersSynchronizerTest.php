<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Synchronizer\Synchronizers;

use App\Models\Customer;
use App\Services\DataLoader\Events\DataImported;
use App\Services\DataLoader\Testing\Helper;
use Illuminate\Support\Facades\Event;
use Tests\Data\Services\DataLoader\Synchronizers\CustomersSynchronizerData;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @covers \App\Services\DataLoader\Processors\Synchronizer\Synchronizers\CustomersSynchronizer
 */
class CustomersSynchronizerTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    public function testProcess(): void {
        // Generate
        $this->generateData(CustomersSynchronizerData::class);

        // Setup
        $this->overrideDateFactory('2022-09-27T00:00:00.000+00:00');
        $this->overrideUuidFactory('8c51a0e4-7439-41ca-83c5-e653d8152425');

        // Pretest
        self::assertModelsCount([
            Customer::class => 1,
        ]);

        // Test (cold)
        $queries = $this->getQueryLog()->flush();
        $events  = Event::fake(DataImported::class);

        $this->app->make(CustomersSynchronizer::class)
            ->setChunkSize(CustomersSynchronizerData::CHUNK)
            ->start();

        self::assertQueryLogEquals('~process-cold-queries.json', $queries);
        self::assertModelsCount([
            Customer::class => 25,
        ]);
        self::assertDispatchedEventsEquals(
            '~process-cold-events.json',
            $events->dispatched(DataImported::class),
        );

        unset($events);

        // Test (hot)
        $queries = $this->getQueryLog()->flush();
        $events  = Event::fake(DataImported::class);

        $this->app->make(CustomersSynchronizer::class)
            ->setChunkSize(CustomersSynchronizerData::CHUNK)
            ->setWithOutdated(false)
            ->start();

        self::assertQueryLogEquals('~process-hot-queries.json', $queries);
        self::assertDispatchedEventsEquals(
            '~process-hot-events.json',
            $events->dispatched(DataImported::class),
        );

        unset($events);
    }
}
