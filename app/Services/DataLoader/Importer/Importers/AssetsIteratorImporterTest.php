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
use Tests\Data\Services\DataLoader\Importers\AssetsIteratorImporterDataWithDocuments;
use Tests\Data\Services\DataLoader\Importers\AssetsIteratorImporterDataWithoutDocuments;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Importer\Importers\AssetsIteratorImporter
 */
class AssetsIteratorImporterTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    /**
     * @covers ::run
     */
    public function testImportWithDocuments(): void {
        // Generate
        $this->generateData(AssetsIteratorImporterDataWithDocuments::class);

        // Pretest
        $this->assertModelsCount([
            Distributor::class   => 2,
            Reseller::class      => 20,
            Customer::class      => 11,
            Asset::class         => 0,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);

        // Test (cold)
        $queries = $this->getQueryLog();
        $events  = Event::fake(DataImported::class);

        $this->app->make(AssetsIteratorImporter::class)
            ->setUpdate(true)
            ->setIterator(AssetsIteratorImporterDataWithDocuments::getIterator())
            ->setWithDocuments(AssetsIteratorImporterDataWithDocuments::DOCUMENTS)
            ->setChunkSize(AssetsIteratorImporterDataWithDocuments::CHUNK)
            ->setLimit(AssetsIteratorImporterDataWithDocuments::LIMIT)
            ->start();

        $this->assertQueryLogEquals('~run-with-documents-cold.json', $queries);
        $this->assertModelsCount([
            Distributor::class   => 2,
            Reseller::class      => 20,
            Customer::class      => 11,
            Asset::class         => 10,
            AssetWarranty::class => 16,
            Document::class      => 15,
            DocumentEntry::class => 33,
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

        $this->app->make(AssetsIteratorImporter::class)
            ->setUpdate(true)
            ->setIterator(AssetsIteratorImporterDataWithDocuments::getIterator())
            ->setWithDocuments(AssetsIteratorImporterDataWithDocuments::DOCUMENTS)
            ->setChunkSize(AssetsIteratorImporterDataWithDocuments::CHUNK)
            ->setLimit(AssetsIteratorImporterDataWithDocuments::LIMIT)
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
        $this->generateData(AssetsIteratorImporterDataWithoutDocuments::class);

        // Pretest
        $this->assertModelsCount([
            Distributor::class   => 0,
            Reseller::class      => 30,
            Customer::class      => 10,
            Asset::class         => 0,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);

        // Test (cold)
        $queries = $this->getQueryLog();
        $events  = Event::fake(DataImported::class);

        $this->app->make(AssetsIteratorImporter::class)
            ->setUpdate(true)
            ->setIterator(AssetsIteratorImporterDataWithoutDocuments::getIterator())
            ->setWithDocuments(AssetsIteratorImporterDataWithoutDocuments::DOCUMENTS)
            ->setChunkSize(AssetsIteratorImporterDataWithoutDocuments::CHUNK)
            ->setLimit(AssetsIteratorImporterDataWithoutDocuments::LIMIT)
            ->start();

        $this->assertQueryLogEquals('~run-without-documents-cold.json', $queries);
        $this->assertModelsCount([
            Distributor::class   => 0,
            Reseller::class      => 30,
            Customer::class      => 10,
            Asset::class         => 10,
            AssetWarranty::class => 2,
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

        $this->app->make(AssetsIteratorImporter::class)
            ->setUpdate(true)
            ->setIterator(AssetsIteratorImporterDataWithoutDocuments::getIterator())
            ->setWithDocuments(AssetsIteratorImporterDataWithoutDocuments::DOCUMENTS)
            ->setChunkSize(AssetsIteratorImporterDataWithoutDocuments::CHUNK)
            ->setLimit(AssetsIteratorImporterDataWithoutDocuments::LIMIT)
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
