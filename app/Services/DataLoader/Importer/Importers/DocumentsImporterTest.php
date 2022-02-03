<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers;

use App\Models\Document;
use App\Services\DataLoader\Testing\Helper;
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

        $this->app->make(DocumentsImporter::class)
            ->setUpdate(true)
            ->setLimit(DocumentsImporterData::LIMIT)
            ->setChunkSize(DocumentsImporterData::CHUNK)
            ->start();

        $this->assertQueryLogEquals('~run-cold.json', $queries);
        $this->assertModelsCount([
            Document::class => DocumentsImporterData::LIMIT,
        ]);

        $queries->flush();

        // Test (hot)
        $queries = $this->getQueryLog();

        $this->app->make(DocumentsImporter::class)
            ->setUpdate(true)
            ->setLimit(DocumentsImporterData::LIMIT)
            ->setChunkSize(DocumentsImporterData::CHUNK)
            ->start();

        $this->assertQueryLogEquals('~run-hot.json', $queries);

        $queries->flush();
    }
}
