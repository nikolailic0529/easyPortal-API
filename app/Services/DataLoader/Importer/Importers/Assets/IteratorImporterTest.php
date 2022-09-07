<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers\Assets;

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
 * @coversDefaultClass \App\Services\DataLoader\Importer\Importers\Assets\IteratorImporter
 */
class IteratorImporterTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    /**
     * @covers ::process
     */
    public function testProcessWithDocuments(): void {
        // Generate
        $this->generateData(AssetsIteratorImporterDataWithDocuments::class);

        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('97eb0b2c-a993-4cf6-89f1-7c179b252571');

        // Pretest
        self::assertModelsCount([
            Distributor::class   => 2,
            Reseller::class      => 4,
            Customer::class      => 10,
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
            ->setIterator(AssetsIteratorImporterDataWithDocuments::getIterator())
            ->setWithDocuments(AssetsIteratorImporterDataWithDocuments::DOCUMENTS)
            ->setChunkSize(AssetsIteratorImporterDataWithDocuments::CHUNK)
            ->setLimit(AssetsIteratorImporterDataWithDocuments::LIMIT)
            ->start();

        self::assertQueryLogEquals('~process-with-documents-cold-queries.json', $queries);
        self::assertModelsCount([
            Distributor::class   => 2,
            Reseller::class      => 4,
            Customer::class      => 10,
            Asset::class         => 10,
            AssetWarranty::class => 11,
            Document::class      => 13,
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

        $this->app->make(IteratorImporter::class)
            ->setUpdate(true)
            ->setIterator(AssetsIteratorImporterDataWithDocuments::getIterator())
            ->setWithDocuments(AssetsIteratorImporterDataWithDocuments::DOCUMENTS)
            ->setChunkSize(AssetsIteratorImporterDataWithDocuments::CHUNK)
            ->setLimit(AssetsIteratorImporterDataWithDocuments::LIMIT)
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
        $this->generateData(AssetsIteratorImporterDataWithoutDocuments::class);

        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('381a7352-0b68-4ec3-a58a-81bb9cb63716');

        // Pretest
        self::assertModelsCount([
            Distributor::class   => 0,
            Reseller::class      => 6,
            Customer::class      => 10,
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
            ->setIterator(AssetsIteratorImporterDataWithoutDocuments::getIterator())
            ->setWithDocuments(AssetsIteratorImporterDataWithoutDocuments::DOCUMENTS)
            ->setChunkSize(AssetsIteratorImporterDataWithoutDocuments::CHUNK)
            ->setLimit(AssetsIteratorImporterDataWithoutDocuments::LIMIT)
            ->start();

        self::assertQueryLogEquals('~process-without-documents-cold-queries.json', $queries);
        self::assertModelsCount([
            Distributor::class   => 0,
            Reseller::class      => 6,
            Customer::class      => 10,
            Asset::class         => 10,
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

        $this->app->make(IteratorImporter::class)
            ->setUpdate(true)
            ->setIterator(AssetsIteratorImporterDataWithoutDocuments::getIterator())
            ->setWithDocuments(AssetsIteratorImporterDataWithoutDocuments::DOCUMENTS)
            ->setChunkSize(AssetsIteratorImporterDataWithoutDocuments::CHUNK)
            ->setLimit(AssetsIteratorImporterDataWithoutDocuments::LIMIT)
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
