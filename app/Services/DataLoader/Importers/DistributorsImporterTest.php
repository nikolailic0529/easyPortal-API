<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importers;

use App\Models\Distributor;
use App\Services\DataLoader\Testing\Helper;
use Tests\Data\Services\DataLoader\Importers\DistributorsImporterData;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Importers\DistributorsImporter
 */
class DistributorsImporterTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    /**
     * @covers ::import
     */
    public function testImport(): void {
        // Generate
        $this->generateData(DistributorsImporterData::class);

        // Pretest
        $this->assertModelsCount([
            Distributor::class => 0,
        ]);

        // Test (cold)
        $queries  = $this->getQueryLog();
        $importer = $this->app->make(DistributorsImporter::class);

        $importer->import(true, chunk: DistributorsImporterData::CHUNK, limit: DistributorsImporterData::LIMIT);

        $this->assertQueryLogEquals('~import-cold.json', $queries);
        $this->assertModelsCount([
            Distributor::class => 1,
        ]);

        $queries->flush();

        // Test (hot)
        $queries  = $this->getQueryLog();
        $importer = $this->app->make(DistributorsImporter::class);

        $importer->import(true, chunk: DistributorsImporterData::CHUNK, limit: DistributorsImporterData::LIMIT);

        $this->assertQueryLogEquals('~import-hot.json', $queries);

        $queries->flush();
    }
}
