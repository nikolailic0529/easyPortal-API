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
use Tests\Data\Services\DataLoader\Loaders\CustomerLoaderCreateWithAssets;
use Tests\Data\Services\DataLoader\Loaders\CustomerLoaderCreateWithoutAssets;
use Tests\TestCase;

use function count;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Loaders\CustomerLoader
 */
class CustomerLoaderTest extends TestCase {
    use WithQueryLog;
    use Helper;

    /**
     * @covers ::create
     */
    public function testCreateWithoutAssets(): void {
        // Generate
        $this->generateData(CustomerLoaderCreateWithoutAssets::class);

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
        $importer = $this->app->make(CustomerLoader::class)
            ->setWithAssets(CustomerLoaderCreateWithoutAssets::ASSETS)
            ->setWithAssetsDocuments(CustomerLoaderCreateWithoutAssets::ASSETS);

        $importer->create(CustomerLoaderCreateWithoutAssets::CUSTOMER);

        $actual   = $this->cleanupQueryLog($queries->get());
        $expected = $this->getTestData()->json('~create-without-assets.json');

        $this->assertCount(count($expected), $actual);
        $this->assertEquals($expected, $actual);
        $this->assertModelsCount([
            Distributor::class   => 0,
            Reseller::class      => 0,
            Customer::class      => 1,
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
        $this->generateData(CustomerLoaderCreateWithAssets::class);

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
        $importer = $this->app->make(CustomerLoader::class)
            ->setWithAssets(CustomerLoaderCreateWithAssets::ASSETS)
            ->setWithAssetsDocuments(CustomerLoaderCreateWithAssets::ASSETS);

        $importer->create(CustomerLoaderCreateWithAssets::CUSTOMER);

        $actual   = $this->cleanupQueryLog($queries->get());
        $expected = $this->getTestData()->json('~create-with-assets.json');

        $this->assertCount(count($expected), $actual);
        $this->assertEquals($expected, $actual);
        $this->assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 1,
            Customer::class      => 1,
            Asset::class         => 2,
            AssetWarranty::class => 2,
            Document::class      => 1,
            DocumentEntry::class => 2,
        ]);

        $queries->flush();
    }
}
