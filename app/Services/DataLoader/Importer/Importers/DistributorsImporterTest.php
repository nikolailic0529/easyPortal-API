<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers;

use App\Models\Distributor;
use App\Services\DataLoader\Events\DataImported;
use App\Services\DataLoader\Testing\Helper;
use Illuminate\Support\Facades\Event;
use Tests\Data\Services\DataLoader\Importers\DistributorsImporterData;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Importer\Importers\DistributorsImporter
 */
class DistributorsImporterTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    /**
     * @covers ::run
     */
    public function testImport(): void {
        // Generate
        $this->generateData(DistributorsImporterData::class);

        // Pretest
        $this->assertModelsCount([
            Distributor::class => 0,
        ]);

        // Test (cold)
        $queries = $this->getQueryLog();
        $events  = Event::fake(DataImported::class);

        $this->app->make(DistributorsImporter::class)
            ->setUpdate(true)
            ->setLimit(DistributorsImporterData::LIMIT)
            ->setChunkSize(DistributorsImporterData::CHUNK)
            ->start();

        $this->assertQueryLogEquals('~run-cold.json', $queries);
        $this->assertModelsCount([
            Distributor::class => 5,
        ]);
        $this->assertDispatchedEventsEquals(
            '~run-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);

        // Test (hot)
        $queries = $this->getQueryLog();
        $events  = Event::fake(DataImported::class);

        $this->app->make(DistributorsImporter::class)
            ->setUpdate(true)
            ->setLimit(DistributorsImporterData::LIMIT)
            ->setChunkSize(DistributorsImporterData::CHUNK)
            ->start();

        $this->assertQueryLogEquals('~run-hot.json', $queries);
        $this->assertDispatchedEventsEquals(
            '~run-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);
    }
}
