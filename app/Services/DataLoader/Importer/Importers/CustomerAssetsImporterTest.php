<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers;

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
use Tests\Data\Services\DataLoader\Importers\CustomerAssetsImporterDataWithDocuments;
use Tests\Data\Services\DataLoader\Importers\CustomerAssetsImporterDataWithoutDocuments;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Importer\Importers\CustomerAssetsImporter
 */
class CustomerAssetsImporterTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    /**
     * @covers ::run
     */
    public function testImportWithDocuments(): void {
        // Generate
        $this->generateData(CustomerAssetsImporterDataWithDocuments::class);

        // Pretest
        $this->assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 3,
            Customer::class      => 2,
            Asset::class         => 0,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);

        // Test (cold)
        $queries = $this->getQueryLog();
        $events  = Event::fake(DataImported::class);

        $this->app->make(CustomerAssetsImporter::class)
            ->setUpdate(true)
            ->setCustomerId(CustomerAssetsImporterDataWithDocuments::CUSTOMER)
            ->setWithDocuments(CustomerAssetsImporterDataWithDocuments::DOCUMENTS)
            ->setChunkSize(CustomerAssetsImporterDataWithDocuments::CHUNK)
            ->setLimit(CustomerAssetsImporterDataWithDocuments::LIMIT)
            ->start();

        $this->assertQueryLogEquals('~run-with-documents-cold.json', $queries);
        $this->assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 3,
            Customer::class      => 2,
            Asset::class         => CustomerAssetsImporterDataWithDocuments::LIMIT,
            AssetWarranty::class => 56,
            Document::class      => 4,
            DocumentEntry::class => 58,
        ]);
        $this->assertDispatchedEventsEquals(
            '~run-with-documents-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);

        // Test (hot)
        $queries = $this->getQueryLog();
        $events  = Event::fake(DataImported::class);

        $this->app->make(CustomerAssetsImporter::class)
            ->setUpdate(true)
            ->setCustomerId(CustomerAssetsImporterDataWithDocuments::CUSTOMER)
            ->setWithDocuments(CustomerAssetsImporterDataWithDocuments::DOCUMENTS)
            ->setChunkSize(CustomerAssetsImporterDataWithDocuments::CHUNK)
            ->setLimit(CustomerAssetsImporterDataWithDocuments::LIMIT)
            ->start();

        $this->assertQueryLogEquals('~run-with-documents-hot.json', $queries);
        $this->assertDispatchedEventsEquals(
            '~run-with-documents-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);
    }

    /**
     * @coversNothing
     */
    public function testImportWithoutDocuments(): void {
        // Generate
        $this->generateData(CustomerAssetsImporterDataWithoutDocuments::class);

        // Pretest
        $this->assertModelsCount([
            Distributor::class   => 0,
            Reseller::class      => 0,
            Customer::class      => 1,
            Asset::class         => 0,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);

        // Test (cold)
        $queries = $this->getQueryLog();
        $events  = Event::fake(DataImported::class);

        $this->app->make(CustomerAssetsImporter::class)
            ->setUpdate(true)
            ->setCustomerId(CustomerAssetsImporterDataWithoutDocuments::CUSTOMER)
            ->setWithDocuments(CustomerAssetsImporterDataWithoutDocuments::DOCUMENTS)
            ->setChunkSize(CustomerAssetsImporterDataWithoutDocuments::CHUNK)
            ->setLimit(CustomerAssetsImporterDataWithoutDocuments::LIMIT)
            ->start();

        $this->assertQueryLogEquals('~run-without-documents-cold.json', $queries);
        $this->assertModelsCount([
            Distributor::class   => 0,
            Reseller::class      => 0,
            Customer::class      => 1,
            Asset::class         => CustomerAssetsImporterDataWithoutDocuments::LIMIT,
            AssetWarranty::class => 8,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);
        $this->assertDispatchedEventsEquals(
            '~run-without-documents-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);

        // Test (hot)
        $queries = $this->getQueryLog();
        $events  = Event::fake(DataImported::class);

        $this->app->make(CustomerAssetsImporter::class)
            ->setUpdate(true)
            ->setCustomerId(CustomerAssetsImporterDataWithoutDocuments::CUSTOMER)
            ->setWithDocuments(CustomerAssetsImporterDataWithoutDocuments::DOCUMENTS)
            ->setChunkSize(CustomerAssetsImporterDataWithoutDocuments::CHUNK)
            ->setLimit(CustomerAssetsImporterDataWithoutDocuments::LIMIT)
            ->start();

        $this->assertQueryLogEquals('~run-without-documents-hot.json', $queries);
        $this->assertDispatchedEventsEquals(
            '~run-without-documents-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);
    }
}
