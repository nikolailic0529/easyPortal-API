<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Importer\Importers\Customers;

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
 * @covers \App\Services\DataLoader\Processors\Importer\Importers\Customers\BaseImporter
 * @covers \App\Services\DataLoader\Processors\Importer\Importers\Customers\IteratorImporter
 */
class IteratorImporterTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    public function testProcess(): void {
        // Generate
        $this->generateData(CustomersIteratorImporterData::class);

        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('73778371-e314-4ef2-96af-520afe975e1c');

        // Pretest
        self::assertModelsCount([
            Distributor::class   => 0,
            Reseller::class      => 5,
            Customer::class      => 1,
            Asset::class         => 0,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);

        // Test (cold)
        $iterator = CustomersIteratorImporterData::getIterator();
        $queries  = $this->getQueryLog()->flush();
        $events   = Event::fake(DataImported::class);

        $this->app->make(IteratorImporter::class)
            ->setIterator($iterator)
            ->setChunkSize(CustomersIteratorImporterData::CHUNK)
            ->setLimit(CustomersIteratorImporterData::LIMIT)
            ->start();

        self::assertQueryLogEquals('~process-cold-queries.json', $queries);
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
            '~process-cold-events.json',
            $events->dispatched(DataImported::class),
        );

        unset($events);

        // Test (hot)
        $iterator = CustomersIteratorImporterData::getIterator();
        $queries  = $this->getQueryLog()->flush();
        $events   = Event::fake(DataImported::class);

        $this->app->make(IteratorImporter::class)
            ->setIterator($iterator)
            ->setChunkSize(CustomersIteratorImporterData::CHUNK)
            ->setLimit(CustomersIteratorImporterData::LIMIT)
            ->start();

        self::assertQueryLogEquals('~process-hot-queries.json', $queries);
        self::assertDispatchedEventsEquals(
            '~process-hot-events.json',
            $events->dispatched(DataImported::class),
        );

        unset($events);
    }
}
