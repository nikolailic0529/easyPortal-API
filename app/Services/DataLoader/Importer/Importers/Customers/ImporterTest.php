<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers\Customers;

use App\Models\Customer;
use App\Services\DataLoader\Events\DataImported;
use App\Services\DataLoader\Testing\Helper;
use Illuminate\Support\Facades\Event;
use Tests\Data\Services\DataLoader\Importers\CustomersImporterData;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Importer\Importers\Customers\Importer
 */
class ImporterTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    /**
     * @covers ::run
     */
    public function testImport(): void {
        // Generate
        $this->generateData(CustomersImporterData::class);

        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('4eeb8f32-88b5-4c36-8e1a-97db16eea2a0');

        // Pretest
        self::assertModelsCount([
            Customer::class => 0,
        ]);

        // Test (cold)
        $queries = $this->getQueryLog();
        $events  = Event::fake(DataImported::class);

        $this->app->make(Importer::class)
            ->setUpdate(true)
            ->setLimit(CustomersImporterData::LIMIT)
            ->setChunkSize(CustomersImporterData::CHUNK)
            ->start();

        self::assertQueryLogEquals('~run-cold.json', $queries);
        self::assertModelsCount([
            Customer::class => CustomersImporterData::LIMIT,
        ]);
        self::assertDispatchedEventsEquals(
            '~run-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);

        // Test (hot)
        $queries = $this->getQueryLog();
        $events  = Event::fake(DataImported::class);

        $this->app->make(Importer::class)
            ->setUpdate(true)
            ->setLimit(CustomersImporterData::LIMIT)
            ->setChunkSize(CustomersImporterData::CHUNK)
            ->start();

        self::assertQueryLogEquals('~run-hot.json', $queries);
        self::assertDispatchedEventsEquals(
            '~run-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);
    }
}
