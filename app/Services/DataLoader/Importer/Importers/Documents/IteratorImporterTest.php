<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers\Documents;

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
use Tests\Data\Services\DataLoader\Importers\DocumentsIteratorImporterData;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Importer\Importers\Documents\IteratorImporter
 */
class IteratorImporterTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    /**
     * @covers ::process
     */
    public function testProcess(): void {
        // Generate
        $this->generateData(DocumentsIteratorImporterData::class);

        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('01d6bcb7-7174-4aac-a013-dd0b26c12f6b');

        // Pretest
        self::assertModelsCount([
            Distributor::class   => 4,
            Reseller::class      => 7,
            Customer::class      => 4,
            Asset::class         => 6,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);

        // Test (cold)
        $queries = $this->getQueryLog();
        $events  = Event::fake(DataImported::class);

        $this->app->make(IteratorImporter::class)
            ->setUpdate(true)
            ->setIterator(DocumentsIteratorImporterData::getIterator())
            ->setChunkSize(DocumentsIteratorImporterData::CHUNK)
            ->setLimit(DocumentsIteratorImporterData::LIMIT)
            ->start();

        self::assertQueryLogEquals('~process-cold.json', $queries);
        self::assertModelsCount([
            Distributor::class   => 4,
            Reseller::class      => 7,
            Customer::class      => 4,
            Asset::class         => 6,
            AssetWarranty::class => 0,
            Document::class      => 4,
            DocumentEntry::class => 10,
        ]);
        self::assertDispatchedEventsEquals(
            '~process-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);

        // Test (hot)
        $queries = $this->getQueryLog();
        $events  = Event::fake(DataImported::class);

        $this->app->make(IteratorImporter::class)
            ->setUpdate(true)
            ->setIterator(DocumentsIteratorImporterData::getIterator())
            ->setChunkSize(DocumentsIteratorImporterData::CHUNK)
            ->setLimit(DocumentsIteratorImporterData::LIMIT)
            ->start();

        self::assertQueryLogEquals('~process-hot.json', $queries);
        self::assertDispatchedEventsEquals(
            '~process-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);
    }
}
