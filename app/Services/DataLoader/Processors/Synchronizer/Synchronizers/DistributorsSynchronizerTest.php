<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Synchronizer\Synchronizers;

use App\Models\Distributor;
use App\Services\DataLoader\Events\DataImported;
use App\Services\DataLoader\Testing\Helper;
use Illuminate\Support\Facades\Event;
use Tests\Data\Services\DataLoader\Synchronizers\DistributorsSynchronizerData;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @covers \App\Services\DataLoader\Processors\Synchronizer\Synchronizers\DistributorsSynchronizer
 */
class DistributorsSynchronizerTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    public function testProcess(): void {
        // Generate
        $this->generateData(DistributorsSynchronizerData::class);

        // Setup
        $this->overrideDateFactory('2022-09-16T00:00:00.000+00:00');
        $this->overrideUuidFactory('ffd7b766-3a38-403c-a05f-cc53ba81a5f1');

        // Pretest
        self::assertModelsCount([
            Distributor::class => 1,
        ]);

        // Test (cold)
        $queries = $this->getQueryLog()->flush();
        $events  = Event::fake(DataImported::class);

        $this->app->make(DistributorsSynchronizer::class)
            ->setChunkSize(DistributorsSynchronizerData::CHUNK)
            ->start();

        self::assertQueryLogEquals('~process-cold-queries.json', $queries);
        self::assertModelsCount([
            Distributor::class => 4,
        ]);
        self::assertDispatchedEventsEquals(
            '~process-cold-events.json',
            $events->dispatched(DataImported::class),
        );

        unset($events);

        // Test (hot)
        $queries = $this->getQueryLog()->flush();
        $events  = Event::fake(DataImported::class);

        $this->app->make(DistributorsSynchronizer::class)
            ->setChunkSize(DistributorsSynchronizerData::CHUNK)
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
