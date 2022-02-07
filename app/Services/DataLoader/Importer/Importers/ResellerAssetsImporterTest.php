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
use Tests\Data\Services\DataLoader\Importers\ResellerAssetsImporterDataWithDocuments;
use Tests\Data\Services\DataLoader\Importers\ResellerAssetsImporterDataWithoutDocuments;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Importer\Importers\ResellerAssetsImporter
 */
class ResellerAssetsImporterTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    /**
     * @covers ::run
     */
    public function testImportWithDocuments(): void {
        // Generate
        $this->generateData(ResellerAssetsImporterDataWithDocuments::class);

        // Pretest
        $this->assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 5,
            Customer::class      => 4,
            Asset::class         => 0,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);

        // Test (cold)
        $queries = $this->getQueryLog();
        $events  = Event::fake(DataImported::class);

        $this->app->make(ResellerAssetsImporter::class)
            ->setUpdate(true)
            ->setResellerId(ResellerAssetsImporterDataWithDocuments::RESELLER)
            ->setWithDocuments(ResellerAssetsImporterDataWithDocuments::DOCUMENTS)
            ->setChunkSize(ResellerAssetsImporterDataWithDocuments::CHUNK)
            ->setLimit(ResellerAssetsImporterDataWithDocuments::LIMIT)
            ->start();

        $this->assertQueryLogEquals('~run-with-documents-cold.json', $queries);
        $this->assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 5,
            Customer::class      => 4,
            Asset::class         => 50,
            AssetWarranty::class => 64,
            Document::class      => 19,
            DocumentEntry::class => 86,
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

        $this->app->make(ResellerAssetsImporter::class)
            ->setUpdate(true)
            ->setResellerId(ResellerAssetsImporterDataWithDocuments::RESELLER)
            ->setWithDocuments(ResellerAssetsImporterDataWithDocuments::DOCUMENTS)
            ->setChunkSize(ResellerAssetsImporterDataWithDocuments::CHUNK)
            ->setLimit(ResellerAssetsImporterDataWithDocuments::LIMIT)
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
        $this->generateData(ResellerAssetsImporterDataWithoutDocuments::class);

        // Pretest
        $this->assertModelsCount([
            Distributor::class   => 0,
            Reseller::class      => 3,
            Customer::class      => 1,
            Asset::class         => 0,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);

        // Test (cold)
        $queries = $this->getQueryLog();
        $events  = Event::fake(DataImported::class);

        $this->app->make(ResellerAssetsImporter::class)
            ->setUpdate(true)
            ->setResellerId(ResellerAssetsImporterDataWithoutDocuments::RESELLER)
            ->setWithDocuments(ResellerAssetsImporterDataWithoutDocuments::DOCUMENTS)
            ->setChunkSize(ResellerAssetsImporterDataWithoutDocuments::CHUNK)
            ->setLimit(ResellerAssetsImporterDataWithoutDocuments::LIMIT)
            ->start();

        $this->assertQueryLogEquals('~run-without-documents-cold.json', $queries);
        $this->assertModelsCount([
            Distributor::class   => 0,
            Reseller::class      => 3,
            Customer::class      => 1,
            Asset::class         => 50,
            AssetWarranty::class => 0,
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

        $this->app->make(ResellerAssetsImporter::class)
            ->setUpdate(true)
            ->setResellerId(ResellerAssetsImporterDataWithoutDocuments::RESELLER)
            ->setWithDocuments(ResellerAssetsImporterDataWithoutDocuments::DOCUMENTS)
            ->setChunkSize(ResellerAssetsImporterDataWithoutDocuments::CHUNK)
            ->setLimit(ResellerAssetsImporterDataWithoutDocuments::LIMIT)
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
