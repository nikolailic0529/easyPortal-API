<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importers;

use App\Models\Customer;
use App\Services\DataLoader\Testing\Helper;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Tests\Data\Services\DataLoader\Importers\CustomersImporterData;
use Tests\TestCase;

use function count;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Importers\CustomersImporter
 */
class CustomersImporterTest extends TestCase {
    use WithQueryLog;
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

        $actual   = $this->cleanupQueryLog($queries->get());
        $expected = $this->getTestData()->json('~import-cold.json');

        $this->assertCount(count($expected), $actual);
        $this->assertEquals($expected, $actual);
        $this->assertModelsCount([
            Customer::class => CustomersImporterData::LIMIT,
        ]);

        $queries->flush();

        // Test (hot)
        $queries  = $this->getQueryLog();
        $importer = $this->app->make(CustomersImporter::class);

        $importer->import(true, chunk: CustomersImporterData::CHUNK, limit: CustomersImporterData::LIMIT);

        $actual   = $this->cleanupQueryLog($queries->get());
        $expected = $this->getTestData()->json('~import-hot.json');

        $this->assertCount(count($expected), $actual);
        $this->assertEquals($expected, $actual);

        $queries->flush();
    }
}
