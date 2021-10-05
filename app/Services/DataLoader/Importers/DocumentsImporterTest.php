<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importers;

use App\Models\Document;
use App\Services\DataLoader\Testing\Helper;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Tests\Data\Services\DataLoader\Importers\DocumentsImporterData;
use Tests\TestCase;

use function count;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Importers\DocumentsImporter
 */
class DocumentsImporterTest extends TestCase {
    use WithQueryLog;
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

        $actual   = $this->cleanupQueryLog($queries->get());
        $expected = $this->getTestData()->json('~import-cold.json');

        $this->assertCount(count($expected), $actual);
        $this->assertEquals($expected, $actual);
        $this->assertModelsCount([
            Document::class => DocumentsImporterData::LIMIT,
        ]);

        $queries->flush();

        // Test (hot)
        $queries  = $this->getQueryLog();
        $importer = $this->app->make(DocumentsImporter::class);

        $importer->import(true, chunk: DocumentsImporterData::CHUNK, limit: DocumentsImporterData::LIMIT);

        $actual   = $this->cleanupQueryLog($queries->get());
        $expected = $this->getTestData()->json('~import-hot.json');

        $this->assertCount(count($expected), $actual);
        $this->assertEquals($expected, $actual);

        $queries->flush();
    }
}
