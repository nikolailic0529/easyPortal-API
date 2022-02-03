<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers;

use App\Models\Asset;
use App\Models\AssetWarranty;
use App\Models\Customer;
use App\Models\Distributor;
use App\Models\Document;
use App\Models\DocumentEntry;
use App\Models\Reseller;
use App\Services\DataLoader\Testing\Helper;
use Tests\Data\Services\DataLoader\Importers\AssetsImporterData;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Importer\Importers\AssetsImporter
 */
class AssetsImporterTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    /**
     * @covers ::import
     */
    public function testImport(): void {
        // Generate
        $this->generateData(AssetsImporterData::class);

        // Pretest
        $this->assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 140,
            Customer::class      => 51,
            Asset::class         => 0,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);

        // Test (cold)
        $queries = $this->getQueryLog();

        $this->app->make(AssetsImporter::class)
            ->setUpdate(true)
            ->setLimit(AssetsImporterData::LIMIT)
            ->setChunkSize(AssetsImporterData::CHUNK)
            ->start();

        $this->assertQueryLogEquals('~import-cold.json', $queries);
        $this->assertModelsCount([
            Asset::class         => AssetsImporterData::LIMIT,
            AssetWarranty::class => 110,
            Document::class      => 65,
            DocumentEntry::class => 152,
        ]);

        $queries->flush();

        // Test (hot)
        $queries = $this->getQueryLog();

        $this->app->make(AssetsImporter::class)
            ->setUpdate(true)
            ->setLimit(AssetsImporterData::LIMIT)
            ->setChunkSize(AssetsImporterData::CHUNK)
            ->start();

        $this->assertQueryLogEquals('~import-hot.json', $queries);

        $queries->flush();
    }
}
