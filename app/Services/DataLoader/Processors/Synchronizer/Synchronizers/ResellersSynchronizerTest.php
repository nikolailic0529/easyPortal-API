<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Synchronizer\Synchronizers;

use App\Models\Reseller;
use App\Services\DataLoader\Events\DataImported;
use App\Services\DataLoader\Testing\Helper;
use Illuminate\Support\Facades\Event;
use Tests\Data\Services\DataLoader\Synchronizers\ResellersSynchronizerData;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @covers \App\Services\DataLoader\Processors\Synchronizer\Synchronizers\ResellersSynchronizer
 */
class ResellersSynchronizerTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    public function testProcess(): void {
        // Generate
        $this->generateData(ResellersSynchronizerData::class);

        // Setup
        $this->overrideDateFactory('2022-09-27T00:00:00.000+00:00');
        $this->overrideUuidFactory('0dab40c3-c780-45da-90a6-8ae190b870c3');

        // Pretest
        self::assertModelsCount([
            Reseller::class => 1,
        ]);

        // Test (cold)
        $queries = $this->getQueryLog()->flush();
        $events  = Event::fake(DataImported::class);

        $this->app->make(ResellersSynchronizer::class)
            ->setChunkSize(ResellersSynchronizerData::CHUNK)
            ->start();

        self::assertQueryLogEquals('~process-cold-queries.json', $queries);
        self::assertModelsCount([
            Reseller::class => 25,
        ]);
        self::assertDispatchedEventsEquals(
            '~process-cold-events.json',
            $events->dispatched(DataImported::class),
        );

        unset($events);

        // Test (hot)
        $queries = $this->getQueryLog()->flush();
        $events  = Event::fake(DataImported::class);

        $this->app->make(ResellersSynchronizer::class)
            ->setChunkSize(ResellersSynchronizerData::CHUNK)
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
