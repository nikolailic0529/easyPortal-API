<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers;

use App\Models\Customer;
use App\Services\DataLoader\Testing\Helper;
use Tests\Data\Services\DataLoader\Importers\CustomersImporterData;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Importer\Importers\CustomersImporter
 */
class CustomersImporterTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    /**
     * @covers ::import
     */
    public function testImport(): void {
        // Generate
        $this->generateData(CustomersImporterData::class);

        // Pretest
        $this->assertModelsCount([
            Customer::class => 0,
        ]);

        // Test (cold)
        $queries  = $this->getQueryLog();
        $importer = $this->app->make(CustomersImporter::class);

        $importer->import(true, chunk: CustomersImporterData::CHUNK, limit: CustomersImporterData::LIMIT);

        $this->assertQueryLogEquals('~import-cold.json', $queries);
        $this->assertModelsCount([
            Customer::class => CustomersImporterData::LIMIT,
        ]);

        $queries->flush();

        // Test (hot)
        $queries  = $this->getQueryLog();
        $importer = $this->app->make(CustomersImporter::class);

        $importer->import(true, chunk: CustomersImporterData::CHUNK, limit: CustomersImporterData::LIMIT);

        $this->assertQueryLogEquals('~import-hot.json', $queries);

        $queries->flush();
    }
}
