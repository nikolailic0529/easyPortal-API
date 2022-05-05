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
use Tests\Data\Services\DataLoader\Importers\ResellerDocumentsImporterData;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Importer\Importers\Resellers\DocumentsImporter
 */
class DocumentsImporterTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    /**
     * @covers ::run
     */
    public function testImport(): void {
        // Generate
        $this->generateData(ResellerDocumentsImporterData::class);

        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('4d311a59-5ad5-4bf1-9d0b-ab7127226381');

        // Pretest
        self::assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 20,
            Customer::class      => 10,
            Asset::class         => 11,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);

        // Test (cold)
        $queries = $this->getQueryLog();
        $events  = Event::fake(DataImported::class);

        $this->app->make(DocumentsImporter::class)
            ->setUpdate(true)
            ->setResellerId(ResellerDocumentsImporterData::RESELLER)
            ->setChunkSize(ResellerDocumentsImporterData::CHUNK)
            ->setLimit(ResellerDocumentsImporterData::LIMIT)
            ->start();

        self::assertQueryLogEquals('~run-cold.json', $queries);
        self::assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 20,
            Customer::class      => 10,
            Asset::class         => 11,
            AssetWarranty::class => 0,
            Document::class      => 15,
            DocumentEntry::class => 33,
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
            ->setResellerId(ResellerDocumentsImporterData::RESELLER)
            ->setChunkSize(ResellerDocumentsImporterData::CHUNK)
            ->setLimit(ResellerDocumentsImporterData::LIMIT)
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
