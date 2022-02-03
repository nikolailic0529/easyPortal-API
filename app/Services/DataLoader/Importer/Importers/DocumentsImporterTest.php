<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers;

use App\Models\Document;
use App\Services\DataLoader\Events\DataImported;
use App\Services\DataLoader\Testing\Helper;
use Illuminate\Support\Facades\Event;
use Tests\Data\Services\DataLoader\Importers\DocumentsImporterData;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Importer\Importers\DocumentsImporter
 */
class DocumentsImporterTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    /**
     * @covers ::run
     */
    public function testImport(): void {
        // Generate
        $this->generateData(DocumentsImporterData::class);

        // Pretest
        $this->assertModelsCount([
            Document::class => 0,
        ]);

        // Test (cold)
        $queries = $this->getQueryLog();
        $events  = Event::fake(DataImported::class);

        $this->app->make(DocumentsImporter::class)
            ->setUpdate(true)
            ->setLimit(DocumentsImporterData::LIMIT)
            ->setChunkSize(DocumentsImporterData::CHUNK)
            ->start();

        $this->assertQueryLogEquals('~run-cold.json', $queries);
        $this->assertModelsCount([
            Document::class => DocumentsImporterData::LIMIT,
        ]);
        $this->assertDispatchedEventsEquals(
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
            ->setLimit(DocumentsImporterData::LIMIT)
            ->setChunkSize(DocumentsImporterData::CHUNK)
            ->start();

        $this->assertQueryLogEquals('~run-hot.json', $queries);
        $this->assertDispatchedEventsEquals(
            '~run-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);
    }
}
