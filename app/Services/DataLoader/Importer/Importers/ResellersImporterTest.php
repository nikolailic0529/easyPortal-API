<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers;

use App\Models\Reseller;
use App\Services\DataLoader\Testing\Helper;
use Tests\Data\Services\DataLoader\Importers\ResellersImporterData;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Importer\Importers\ResellersImporter
 */
class ResellersImporterTest extends TestCase {
    use WithQueryLogs;
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
        $queries = $this->getQueryLog();

        $this->app->make(ResellersImporter::class)
            ->setUpdate(true)
            ->setLimit(ResellersImporterData::LIMIT)
            ->setChunkSize(ResellersImporterData::CHUNK)
            ->start();

        $this->assertQueryLogEquals('~import-cold.json', $queries);
        $this->assertModelsCount([
            Reseller::class => ResellersImporterData::LIMIT,
        ]);

        $queries->flush();

        // Test (hot)
        $queries = $this->getQueryLog();

        $this->app->make(ResellersImporter::class)
            ->setUpdate(true)
            ->setLimit(ResellersImporterData::LIMIT)
            ->setChunkSize(ResellersImporterData::CHUNK)
            ->start();

        $this->assertQueryLogEquals('~import-hot.json', $queries);

        $queries->flush();
    }
}
