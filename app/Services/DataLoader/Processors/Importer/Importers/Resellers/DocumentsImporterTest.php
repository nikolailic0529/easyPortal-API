<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Importer\Importers\Resellers;

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
 * @covers \App\Services\DataLoader\Processors\Importer\Importers\Documents\BaseImporter
 * @covers \App\Services\DataLoader\Processors\Importer\Importers\Resellers\DocumentsImporter
 */
class DocumentsImporterTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    public function testProcess(): void {
        // Generate
        $this->generateData(ResellerDocumentsImporterData::class);

        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('4d311a59-5ad5-4bf1-9d0b-ab7127226381');

        // Pretest
        self::assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 1,
            Customer::class      => 14,
            Asset::class         => 74,
            AssetWarranty::class => 64,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);

        // Test (cold)
        $queries = $this->getQueryLog();
        $events  = Event::fake(DataImported::class);

        $this->app->make(DocumentsImporter::class)
            ->setObjectId(ResellerDocumentsImporterData::RESELLER)
            ->setChunkSize(ResellerDocumentsImporterData::CHUNK)
            ->setLimit(ResellerDocumentsImporterData::LIMIT)
            ->start();

        self::assertQueryLogEquals('~process-cold-queries.json', $queries);
        self::assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 1,
            Customer::class      => 14,
            Asset::class         => 74,
            AssetWarranty::class => 64,
            Document::class      => 25,
            DocumentEntry::class => 98,
        ]);
        self::assertDispatchedEventsEquals(
            '~process-cold-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);

        // Test (hot)
        $queries = $this->getQueryLog();
        $events  = Event::fake(DataImported::class);

        $this->app->make(DocumentsImporter::class)
            ->setObjectId(ResellerDocumentsImporterData::RESELLER)
            ->setChunkSize(ResellerDocumentsImporterData::CHUNK)
            ->setLimit(ResellerDocumentsImporterData::LIMIT)
            ->start();

        self::assertQueryLogEquals('~process-hot-queries.json', $queries);
        self::assertDispatchedEventsEquals(
            '~process-hot-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);
    }
}
