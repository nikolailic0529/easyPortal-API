<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers\Distributors;

use App\Models\Distributor;
use App\Services\DataLoader\Events\DataImported;
use App\Services\DataLoader\Testing\Helper;
use Illuminate\Support\Facades\Event;
use Tests\Data\Services\DataLoader\Importers\DistributorsImporterData;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Importer\Importers\Distributors\Importer
 */
class ImporterTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    /**
     * @covers ::process
     */
    public function testProcess(): void {
        // Generate
        $this->generateData(DistributorsImporterData::class);

        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('e75e2c94-6aab-4170-9f40-992c401d0d68');

        // Pretest
        self::assertModelsCount([
            Distributor::class => 0,
        ]);

        // Test (cold)
        $queries = $this->getQueryLog();
        $events  = Event::fake(DataImported::class);

        $this->app->make(Importer::class)
            ->setUpdate(true)
            ->setLimit(DistributorsImporterData::LIMIT)
            ->setChunkSize(DistributorsImporterData::CHUNK)
            ->start();

        self::assertQueryLogEquals('~process-cold.json', $queries);
        self::assertModelsCount([
            Distributor::class => 5,
        ]);
        self::assertDispatchedEventsEquals(
            '~process-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);

        // Test (hot)
        $queries = $this->getQueryLog();
        $events  = Event::fake(DataImported::class);

        $this->app->make(Importer::class)
            ->setUpdate(true)
            ->setLimit(DistributorsImporterData::LIMIT)
            ->setChunkSize(DistributorsImporterData::CHUNK)
            ->start();

        self::assertQueryLogEquals('~process-hot.json', $queries);
        self::assertDispatchedEventsEquals(
            '~process-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);
    }
}
