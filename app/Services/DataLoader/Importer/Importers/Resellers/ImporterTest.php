<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers\Resellers;

use App\Models\Reseller;
use App\Services\DataLoader\Events\DataImported;
use App\Services\DataLoader\Testing\Helper;
use Illuminate\Support\Facades\Event;
use Tests\Data\Services\DataLoader\Importers\ResellersImporterData;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Importer\Importers\Resellers\Importer
 */
class ImporterTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    /**
     * @covers ::process
     */
    public function testProcess(): void {
        // Generate
        $this->generateData(ResellersImporterData::class);

        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('604c0739-1396-414a-9ff4-6f473cd42f33');

        // Pretest
        self::assertModelsCount([
            Reseller::class => 0,
        ]);

        // Test (cold)
        $queries = $this->getQueryLog();
        $events  = Event::fake(DataImported::class);

        $this->app->make(Importer::class)
            ->setUpdate(true)
            ->setLimit(ResellersImporterData::LIMIT)
            ->setChunkSize(ResellersImporterData::CHUNK)
            ->start();

        self::assertQueryLogEquals('~process-cold.json', $queries);
        self::assertModelsCount([
            Reseller::class => ResellersImporterData::LIMIT,
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
            ->setLimit(ResellersImporterData::LIMIT)
            ->setChunkSize(ResellersImporterData::CHUNK)
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
