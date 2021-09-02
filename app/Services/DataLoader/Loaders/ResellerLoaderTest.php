<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders;

use App\Models\Asset;
use App\Models\AssetWarranty;
use App\Models\Customer;
use App\Models\Distributor;
use App\Models\Document;
use App\Models\DocumentEntry;
use App\Models\Reseller;
use App\Services\DataLoader\Testing\Helper;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Tests\Data\Services\DataLoader\Loaders\ResellerLoaderCreateWithAssets;
use Tests\Data\Services\DataLoader\Loaders\ResellerLoaderCreateWithoutAssets;
use Tests\TestCase;

use function count;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Loaders\ResellerLoader
 */
class ResellerLoaderTest extends TestCase {
    use WithQueryLog;
    use Helper;

    /**
     * @covers ::create
     */
    public function testCreateWithoutAssets(): void {
        // Generate
        $this->generateData(ResellerLoaderCreateWithoutAssets::class);

        // Pretest
        $this->assertModelsCount([
            Distributor::class   => 0,
            Reseller::class      => 0,
            Customer::class      => 0,
            Asset::class         => 0,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);

        // Test (cold)
        $queries  = $this->getQueryLog();
        $importer = $this->app->make(ResellerLoader::class)
            ->setWithAssets(ResellerLoaderCreateWithoutAssets::ASSETS)
            ->setWithAssetsDocuments(ResellerLoaderCreateWithoutAssets::ASSETS);

        $importer->create(ResellerLoaderCreateWithoutAssets::RESELLER);

        $actual   = $this->cleanupQueryLog($queries->get());
        $expected = $this->getTestData()->json('~create-without-assets.json');

        $this->assertCount(count($expected), $actual);
        $this->assertEquals($expected, $actual);
        $this->assertModelsCount([
            Distributor::class   => 0,
            Reseller::class      => 1,
            Customer::class      => 0,
            Asset::class         => 0,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);

        $queries->flush();
    }

    /**
     * @covers ::handle
     */
    public function testCreateWithAssets(): void {
        // Generate
        $this->generateData(ResellerLoaderCreateWithAssets::class);

        // Pretest
        $this->assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 1,
            Customer::class      => 1,
            Asset::class         => 0,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);

        // Test (cold)
        $queries  = $this->getQueryLog();
        $importer = $this->app->make(ResellerLoader::class)
            ->setWithAssets(ResellerLoaderCreateWithAssets::ASSETS)
            ->setWithAssetsDocuments(ResellerLoaderCreateWithAssets::ASSETS);

        $importer->create(ResellerLoaderCreateWithAssets::RESELLER);

        $actual   = $this->cleanupQueryLog($queries->get());
        $expected = $this->getTestData()->json('~create-with-assets.json');

        $this->assertCount(count($expected), $actual);
        $this->assertEquals($expected, $actual);
        $this->assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 1,
            Customer::class      => 1,
            Asset::class         => 5,
            AssetWarranty::class => 15,
            Document::class      => 2,
            DocumentEntry::class => 10,
        ]);

        $queries->flush();
    }
}
