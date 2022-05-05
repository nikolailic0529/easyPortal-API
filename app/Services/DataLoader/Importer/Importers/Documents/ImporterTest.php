<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers\Documents;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Distributor;
use App\Models\Document;
use App\Models\DocumentEntry;
use App\Models\Reseller;
use App\Services\DataLoader\Events\DataImported;
use App\Services\DataLoader\Testing\Helper;
use Illuminate\Support\Facades\Event;
use Tests\Data\Services\DataLoader\Importers\DocumentsImporterData;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Importer\Importers\Documents\Importer
 */
class ImporterTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    /**
     * @covers ::run
     */
    public function testImport(): void {
        // Generate
        $this->generateData(DocumentsImporterData::class);

        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('4e3a662e-4953-4c8b-b463-9c2c812bbf46');

        // Pretest
        self::assertModelsCount([
            Distributor::class   => 2,
            Reseller::class      => 39,
            Customer::class      => 16,
            Asset::class         => 88,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);

        // Test (cold)
        $queries = $this->getQueryLog();
        $events  = Event::fake(DataImported::class);

        $this->app->make(Importer::class)
            ->setUpdate(true)
            ->setLimit(DocumentsImporterData::LIMIT)
            ->setOffset(DocumentsImporterData::OFFSET)
            ->setChunkSize(DocumentsImporterData::CHUNK)
            ->start();

        self::assertQueryLogEquals('~run-cold.json', $queries);
        self::assertModelsCount([
            Document::class => DocumentsImporterData::LIMIT,
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

        $this->app->make(Importer::class)
            ->setUpdate(true)
            ->setLimit(DocumentsImporterData::LIMIT)
            ->setOffset(DocumentsImporterData::OFFSET)
            ->setChunkSize(DocumentsImporterData::CHUNK)
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
