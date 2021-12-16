<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importers;

use App\Models\Document;
use App\Services\DataLoader\Testing\Helper;
use Tests\Data\Services\DataLoader\Importers\DocumentsImporterData;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Importers\DocumentsImporter
 */
class DocumentsImporterTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    /**
     * @covers ::import
     */
    public function testImport(): void {
        // Generate
        $this->generateData(DocumentsImporterData::class);

        // Pretest
        $this->assertModelsCount([
            Document::class => 0,
        ]);

        // Test (cold)
        $queries  = $this->getQueryLog();
        $importer = $this->app->make(DocumentsImporter::class);

        $importer->import(true, chunk: DocumentsImporterData::CHUNK, limit: DocumentsImporterData::LIMIT);

        $this->assertQueryLogEquals('~import-cold.json', $queries);
        $this->assertModelsCount([
            Document::class => DocumentsImporterData::LIMIT,
        ]);

        $queries->flush();

        // Test (hot)
        $queries  = $this->getQueryLog();
        $importer = $this->app->make(DocumentsImporter::class);

        $importer->import(true, chunk: DocumentsImporterData::CHUNK, limit: DocumentsImporterData::LIMIT);

        $this->assertQueryLogEquals('~import-hot.json', $queries);

        $queries->flush();
    }
}
