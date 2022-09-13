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
use Tests\Data\Services\DataLoader\Importers\CustomerAssetsImporterDataWithDocuments;
use Tests\Data\Services\DataLoader\Importers\CustomerAssetsImporterDataWithoutDocuments;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Importer\Importers\Customers\AssetsImporter
 */
class AssetsImporterTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    /**
     * @covers ::process
     */
    public function testProcessWithDocuments(): void {
        // Generate
        $this->generateData(CustomerAssetsImporterDataWithDocuments::class);

        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('7e129f96-9a60-4157-a7e8-e629b6d1f5f4');

        // Pretest
        self::assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 1,
            Customer::class      => 1,
            Asset::class         => 0,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);

        // Test (cold)
        $queries = $this->getQueryLog();
        $events  = Event::fake(DataImported::class);

        $this->app->make(AssetsImporter::class)
            ->setObjectId(CustomerAssetsImporterDataWithDocuments::CUSTOMER)
            ->setWithDocuments(CustomerAssetsImporterDataWithDocuments::DOCUMENTS)
            ->setChunkSize(CustomerAssetsImporterDataWithDocuments::CHUNK)
            ->setLimit(CustomerAssetsImporterDataWithDocuments::LIMIT)
            ->start();

        self::assertQueryLogEquals('~process-with-documents-cold-queries.json', $queries);
        self::assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 1,
            Customer::class      => 1,
            Asset::class         => CustomerAssetsImporterDataWithDocuments::LIMIT,
            AssetWarranty::class => 4,
            Document::class      => 3,
            DocumentEntry::class => 0,
        ]);
        self::assertDispatchedEventsEquals(
            '~process-with-documents-cold-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);

        // Test (hot)
        $queries = $this->getQueryLog();
        $events  = Event::fake(DataImported::class);

        $this->app->make(AssetsImporter::class)
            ->setObjectId(CustomerAssetsImporterDataWithDocuments::CUSTOMER)
            ->setWithDocuments(CustomerAssetsImporterDataWithDocuments::DOCUMENTS)
            ->setChunkSize(CustomerAssetsImporterDataWithDocuments::CHUNK)
            ->setLimit(CustomerAssetsImporterDataWithDocuments::LIMIT)
            ->start();

        self::assertQueryLogEquals('~process-with-documents-hot-queries.json', $queries);
        self::assertDispatchedEventsEquals(
            '~process-with-documents-hot-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);
    }

    /**
     * @coversNothing
     */
    public function testProcessWithoutDocuments(): void {
        // Generate
        $this->generateData(CustomerAssetsImporterDataWithoutDocuments::class);

        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('38442267-c7fa-4c7a-a7c8-527ed19cba59');

        // Pretest
        self::assertModelsCount([
            Distributor::class   => 0,
            Reseller::class      => 1,
            Customer::class      => 1,
            Asset::class         => 0,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);

        // Test (cold)
        $queries = $this->getQueryLog();
        $events  = Event::fake(DataImported::class);

        $this->app->make(AssetsImporter::class)
            ->setObjectId(CustomerAssetsImporterDataWithoutDocuments::CUSTOMER)
            ->setWithDocuments(CustomerAssetsImporterDataWithoutDocuments::DOCUMENTS)
            ->setChunkSize(CustomerAssetsImporterDataWithoutDocuments::CHUNK)
            ->setLimit(CustomerAssetsImporterDataWithoutDocuments::LIMIT)
            ->start();

        self::assertQueryLogEquals('~process-without-documents-cold-queries.json', $queries);
        self::assertModelsCount([
            Distributor::class   => 0,
            Reseller::class      => 1,
            Customer::class      => 1,
            Asset::class         => CustomerAssetsImporterDataWithoutDocuments::LIMIT,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);
        self::assertDispatchedEventsEquals(
            '~process-without-documents-cold-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);

        // Test (hot)
        $queries = $this->getQueryLog();
        $events  = Event::fake(DataImported::class);

        $this->app->make(AssetsImporter::class)
            ->setObjectId(CustomerAssetsImporterDataWithoutDocuments::CUSTOMER)
            ->setWithDocuments(CustomerAssetsImporterDataWithoutDocuments::DOCUMENTS)
            ->setChunkSize(CustomerAssetsImporterDataWithoutDocuments::CHUNK)
            ->setLimit(CustomerAssetsImporterDataWithoutDocuments::LIMIT)
            ->start();

        self::assertQueryLogEquals('~process-without-documents-hot-queries.json', $queries);
        self::assertDispatchedEventsEquals(
            '~process-without-documents-hot-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);
    }
}
