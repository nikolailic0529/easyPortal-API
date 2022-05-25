<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers\Resellers;

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
 * @coversDefaultClass \App\Services\DataLoader\Importer\Importers\Resellers\AssetsImporter
 */
class AssetsImporterTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    /**
     * @covers ::process
     */
    public function testProcessWithDocuments(): void {
        // Generate
        $this->generateData(ResellerAssetsImporterDataWithDocuments::class);

        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('8c017603-84cf-49f7-a693-ced728446fc4');

        // Pretest
        self::assertModelsCount([
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

        $this->app->make(AssetsImporter::class)
            ->setUpdate(true)
            ->setObjectId(ResellerAssetsImporterDataWithDocuments::RESELLER)
            ->setWithDocuments(ResellerAssetsImporterDataWithDocuments::DOCUMENTS)
            ->setChunkSize(ResellerAssetsImporterDataWithDocuments::CHUNK)
            ->setLimit(ResellerAssetsImporterDataWithDocuments::LIMIT)
            ->start();

        self::assertQueryLogEquals('~process-with-documents-cold-queries.json', $queries);
        self::assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 5,
            Customer::class      => 4,
            Asset::class         => 50,
            AssetWarranty::class => 64,
            Document::class      => 19,
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
            ->setUpdate(true)
            ->setObjectId(ResellerAssetsImporterDataWithDocuments::RESELLER)
            ->setWithDocuments(ResellerAssetsImporterDataWithDocuments::DOCUMENTS)
            ->setChunkSize(ResellerAssetsImporterDataWithDocuments::CHUNK)
            ->setLimit(ResellerAssetsImporterDataWithDocuments::LIMIT)
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
        $this->generateData(ResellerAssetsImporterDataWithoutDocuments::class);

        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('a7c1572d-a628-46ad-9b4c-966151e444df');

        // Pretest
        self::assertModelsCount([
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

        $this->app->make(AssetsImporter::class)
            ->setUpdate(true)
            ->setObjectId(ResellerAssetsImporterDataWithoutDocuments::RESELLER)
            ->setWithDocuments(ResellerAssetsImporterDataWithoutDocuments::DOCUMENTS)
            ->setChunkSize(ResellerAssetsImporterDataWithoutDocuments::CHUNK)
            ->setLimit(ResellerAssetsImporterDataWithoutDocuments::LIMIT)
            ->start();

        self::assertQueryLogEquals('~process-without-documents-cold-queries.json', $queries);
        self::assertModelsCount([
            Distributor::class   => 0,
            Reseller::class      => 3,
            Customer::class      => 1,
            Asset::class         => 50,
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
            ->setUpdate(true)
            ->setObjectId(ResellerAssetsImporterDataWithoutDocuments::RESELLER)
            ->setWithDocuments(ResellerAssetsImporterDataWithoutDocuments::DOCUMENTS)
            ->setChunkSize(ResellerAssetsImporterDataWithoutDocuments::CHUNK)
            ->setLimit(ResellerAssetsImporterDataWithoutDocuments::LIMIT)
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
