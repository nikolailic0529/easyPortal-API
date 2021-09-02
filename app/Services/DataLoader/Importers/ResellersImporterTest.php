<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importers;

use App\Models\Reseller;
use App\Services\DataLoader\Testing\Helper;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Tests\Data\Services\DataLoader\Importers\ResellersImporterData;
use Tests\TestCase;

use function count;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Importers\ResellersImporter
 */
class ResellersImporterTest extends TestCase {
    use WithQueryLog;
    use Helper;

    /**
     * @covers ::import
     */
    public function testImport(): void {
        // Generate
        $this->generateData(ResellersImporterData::class);

        // Pretest
        $this->assertModelsCount([
            Reseller::class => 0,
        ]);

        // Test (cold)
        $queries  = $this->getQueryLog();
        $importer = $this->app->make(ResellersImporter::class);

        $importer->import(true, chunk: ResellersImporterData::CHUNK, limit: ResellersImporterData::LIMIT);

        $actual   = $this->cleanupQueryLog($queries->get());
        $expected = $this->getTestData()->json('~import-cold.json');

        $this->assertCount(count($expected), $actual);
        $this->assertEquals($expected, $actual);
        $this->assertModelsCount([
            Reseller::class => ResellersImporterData::LIMIT,
        ]);

        $queries->flush();

        // Test (hot)
        $queries  = $this->getQueryLog();
        $importer = $this->app->make(ResellersImporter::class);

        $importer->import(true, chunk: ResellersImporterData::CHUNK, limit: ResellersImporterData::LIMIT);

        $actual   = $this->cleanupQueryLog($queries->get());
        $expected = $this->getTestData()->json('~import-hot.json');

        $this->assertCount(count($expected), $actual);
        $this->assertEquals($expected, $actual);

        $queries->flush();
    }
}
