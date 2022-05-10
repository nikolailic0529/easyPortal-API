<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers\Customers;

use App\Models\Asset;
use App\Models\AssetWarranty;
use App\Models\Customer;
use App\Models\Distributor;
use App\Models\Document;
use App\Models\DocumentEntry;
use App\Models\Reseller;
use App\Services\DataLoader\Events\DataImported;
use App\Services\DataLoader\Testing\Helper;
use Illuminate\Support\Facades\Event;
use Tests\Data\Services\DataLoader\Importers\CustomersIteratorImporterData;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Importer\Importers\Customers\IteratorImporter
 */
class IteratorImporterTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    /**
     * @covers ::process
     */
    public function testProcessWithDocuments(): void {
        // Generate
        $this->generateData(CustomersIteratorImporterData::class);

        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('73778371-e314-4ef2-96af-520afe975e1c');

        // Pretest
        self::assertModelsCount([
            Distributor::class   => 0,
            Reseller::class      => 5,
            Customer::class      => 0,
            Asset::class         => 0,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);

        // Test (cold)
        $queries = $this->getQueryLog();
        $events  = Event::fake(DataImported::class);

        $this->app->make(IteratorImporter::class)
            ->setUpdate(true)
            ->setIterator(CustomersIteratorImporterData::getIterator())
            ->setChunkSize(CustomersIteratorImporterData::CHUNK)
            ->setLimit(CustomersIteratorImporterData::LIMIT)
            ->start();

        self::assertQueryLogEquals('~process-cold.json', $queries);
        self::assertModelsCount([
            Distributor::class   => 0,
            Reseller::class      => 5,
            Customer::class      => 10,
            Asset::class         => 0,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
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

        $this->app->make(IteratorImporter::class)
            ->setUpdate(true)
            ->setIterator(CustomersIteratorImporterData::getIterator())
            ->setChunkSize(CustomersIteratorImporterData::CHUNK)
            ->setLimit(CustomersIteratorImporterData::LIMIT)
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
