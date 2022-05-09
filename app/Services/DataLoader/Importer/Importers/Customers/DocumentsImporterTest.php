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
use Tests\Data\Services\DataLoader\Importers\CustomerDocumentsImporterData;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Importer\Importers\Customers\DocumentsImporter
 */
class DocumentsImporterTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    /**
     * @covers ::run
     */
    public function testImport(): void {
        // Generate
        $this->generateData(CustomerDocumentsImporterData::class);

        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('b6d6b528-e0e9-4470-8b60-7ec2a7da3a83');

        // Pretest
        self::assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 4,
            Customer::class      => 2,
            Asset::class         => 5,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);

        // Test (cold)
        $queries = $this->getQueryLog();
        $events  = Event::fake(DataImported::class);

        $this->app->make(DocumentsImporter::class)
            ->setUpdate(true)
            ->setObjectId(CustomerDocumentsImporterData::CUSTOMER)
            ->setChunkSize(CustomerDocumentsImporterData::CHUNK)
            ->setLimit(CustomerDocumentsImporterData::LIMIT)
            ->start();

        self::assertQueryLogEquals('~run-cold.json', $queries);
        self::assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 4,
            Customer::class      => 2,
            Asset::class         => 5,
            AssetWarranty::class => 0,
            Document::class      => 15,
            DocumentEntry::class => 120,
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

        $this->app->make(DocumentsImporter::class)
            ->setUpdate(true)
            ->setObjectId(CustomerDocumentsImporterData::CUSTOMER)
            ->setChunkSize(CustomerDocumentsImporterData::CHUNK)
            ->setLimit(CustomerDocumentsImporterData::LIMIT)
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
