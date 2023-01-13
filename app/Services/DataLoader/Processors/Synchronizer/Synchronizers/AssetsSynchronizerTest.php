<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Synchronizer\Synchronizers;

use App\Models\Asset;
use App\Services\DataLoader\Events\DataImported;
use App\Services\DataLoader\Testing\Helper;
use Illuminate\Support\Facades\Event;
use Tests\Data\Services\DataLoader\Synchronizers\AssetsSynchronizerData;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @covers \App\Services\DataLoader\Processors\Synchronizer\Synchronizer
 * @covers \App\Services\DataLoader\Processors\Synchronizer\Synchronizers\AssetsSynchronizer
 */
class AssetsSynchronizerTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    public function testProcess(): void {
        // Generate
        $this->generateData(AssetsSynchronizerData::class);

        // Setup
        $this->overrideDateFactory('2022-09-27T00:00:00.000+00:00');
        $this->overrideUuidFactory('47766c3d-11fb-40f6-8c8d-f77701d037fa');

        // Pretest
        self::assertModelsCount([
            Asset::class => 1,
        ]);

        // Test (cold)
        $queries = $this->getQueryLog()->flush();
        $events  = Event::fake(DataImported::class);

        $this->app->make(AssetsSynchronizer::class)
            ->setChunkSize(AssetsSynchronizerData::CHUNK)
            ->start();

        self::assertQueryLogEquals('~process-cold-queries.json', $queries);
        self::assertModelsCount([
            Asset::class => 25,
        ]);
        self::assertDispatchedEventsEquals(
            '~process-cold-events.json',
            $events->dispatched(DataImported::class),
        );

        unset($events);

        // Test (hot)
        $queries = $this->getQueryLog()->flush();
        $events  = Event::fake(DataImported::class);

        $this->app->make(AssetsSynchronizer::class)
            ->setChunkSize(AssetsSynchronizerData::CHUNK)
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
