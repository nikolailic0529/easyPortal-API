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
use Tests\Data\Services\DataLoader\Importers\ResellersIteratorImporterData;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @covers \App\Services\DataLoader\Processors\Importer\Importers\Resellers\IteratorImporter
 */
class IteratorImporterTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    public function testProcessWithDocuments(): void {
        // Generate
        $this->generateData(ResellersIteratorImporterData::class);

        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('e5290a3c-ba53-46c7-9c44-9ca64de284d6');

        // Pretest
        self::assertModelsCount([
            Distributor::class   => 0,
            Reseller::class      => 1,
            Customer::class      => 0,
            Asset::class         => 0,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);

        // Test (cold)
        $iterator = ResellersIteratorImporterData::getIterator();
        $queries  = $this->getQueryLog()->flush();
        $events   = Event::fake(DataImported::class);

        $this->app->make(IteratorImporter::class)
            ->setIterator($iterator)
            ->setChunkSize(ResellersIteratorImporterData::CHUNK)
            ->setLimit(ResellersIteratorImporterData::LIMIT)
            ->start();

        self::assertQueryLogEquals('~process-cold-queries.json', $queries);
        self::assertModelsCount([
            Distributor::class   => 0,
            Reseller::class      => 10,
            Customer::class      => 0,
            Asset::class         => 0,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);
        self::assertDispatchedEventsEquals(
            '~process-cold-events.json',
            $events->dispatched(DataImported::class),
        );

        unset($events);

        // Test (hot)
        $iterator = ResellersIteratorImporterData::getIterator();
        $queries  = $this->getQueryLog()->flush();
        $events   = Event::fake(DataImported::class);

        $this->app->make(IteratorImporter::class)
            ->setIterator($iterator)
            ->setChunkSize(ResellersIteratorImporterData::CHUNK)
            ->setLimit(ResellersIteratorImporterData::LIMIT)
            ->start();

        self::assertQueryLogEquals('~process-hot-queries.json', $queries);
        self::assertDispatchedEventsEquals(
            '~process-hot-events.json',
            $events->dispatched(DataImported::class),
        );

        unset($events);
    }
}
