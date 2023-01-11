<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Importer\Importers\Documents;

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
 * @covers \App\Services\DataLoader\Processors\Importer\Importers\Documents\IteratorImporter
 */
class IteratorImporterTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    public function testProcess(): void {
        // Generate
        $this->generateData(DocumentsIteratorImporterData::class);

        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('01d6bcb7-7174-4aac-a013-dd0b26c12f6b');

        // Pretest
        self::assertModelsCount([
            Distributor::class   => 2,
            Reseller::class      => 7,
            Customer::class      => 10,
            Asset::class         => 24,
            AssetWarranty::class => 43,
            Document::class      => 1,
            DocumentEntry::class => 0,
        ]);

        // Test (cold)
        $iterator = DocumentsIteratorImporterData::getIterator();
        $queries  = $this->getQueryLog()->flush();
        $events   = Event::fake(DataImported::class);

        $this->app->make(IteratorImporter::class)
            ->setIterator($iterator)
            ->setChunkSize(DocumentsIteratorImporterData::CHUNK)
            ->setLimit(DocumentsIteratorImporterData::LIMIT)
            ->start();

        self::assertQueryLogEquals('~process-cold-queries.json', $queries);
        self::assertModelsCount([
            Distributor::class   => 2,
            Reseller::class      => 7,
            Customer::class      => 10,
            Asset::class         => 24,
            AssetWarranty::class => 43,
            Document::class      => 10,
            DocumentEntry::class => 40,
        ]);
        self::assertDispatchedEventsEquals(
            '~process-cold-events.json',
            $events->dispatched(DataImported::class),
        );

        unset($events);

        // Test (hot)
        $iterator = DocumentsIteratorImporterData::getIterator();
        $queries  = $this->getQueryLog()->flush();
        $events   = Event::fake(DataImported::class);

        $this->app->make(IteratorImporter::class)
            ->setIterator($iterator)
            ->setChunkSize(DocumentsIteratorImporterData::CHUNK)
            ->setLimit(DocumentsIteratorImporterData::LIMIT)
            ->start();

        self::assertQueryLogEquals('~process-hot-queries.json', $queries);
        self::assertDispatchedEventsEquals(
            '~process-hot-events.json',
            $events->dispatched(DataImported::class),
        );

        unset($events);
    }
}
