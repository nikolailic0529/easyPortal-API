<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders;

use App\Models\Asset;
use App\Models\AssetWarranty;
use App\Models\Customer;
use App\Models\Distributor;
use App\Models\Document;
use App\Models\DocumentEntry;
use App\Models\Reseller;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Exceptions\CustomerWarrantyCheckFailed;
use App\Services\DataLoader\Testing\Helper;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Mockery\MockInterface;
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
        $importer = $this->app->make(Container::class)
            ->make(CustomerLoader::class)
            ->setWithAssets(CustomerLoaderCreateWithoutAssets::ASSETS)
            ->setWithAssetsDocuments(CustomerLoaderCreateWithoutAssets::ASSETS);

        $importer->create(CustomerLoaderCreateWithoutAssets::CUSTOMER);

        $actual   = $this->cleanupQueryLog($queries->get());
        $expected = $this->getTestData()->json('~create-without-assets-cold.json');

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

        // Test (hot)
        $queries  = $this->getQueryLog();
        $importer = $this->app->make(Container::class)
            ->make(CustomerLoader::class)
            ->setWithAssets(CustomerLoaderCreateWithoutAssets::ASSETS)
            ->setWithAssetsDocuments(CustomerLoaderCreateWithoutAssets::ASSETS);

        $importer->create(CustomerLoaderCreateWithoutAssets::CUSTOMER);

        $actual   = $this->cleanupQueryLog($queries->get());
        $expected = $this->getTestData()->json('~create-without-assets-hot.json');

        $this->assertCount(count($expected), $actual);
        $this->assertEquals($expected, $actual);

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
            Reseller::class      => 2,
            Customer::class      => 1,
            Asset::class         => 0,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);

        // Test (cold)
        $queries  = $this->getQueryLog();
        $importer = $this->app->make(Container::class)
            ->make(CustomerLoader::class)
            ->setWithAssets(CustomerLoaderCreateWithAssets::ASSETS)
            ->setWithAssetsDocuments(CustomerLoaderCreateWithAssets::ASSETS);

        $importer->create(CustomerLoaderCreateWithAssets::CUSTOMER);

        $actual   = $this->cleanupQueryLog($queries->get());
        $expected = $this->getTestData()->json('~create-with-assets-cold.json');

        $this->assertCount(count($expected), $actual);
        $this->assertEquals($expected, $actual);
        $this->assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 2,
            Customer::class      => 1,
            Asset::class         => 2,
            AssetWarranty::class => 14,
            Document::class      => 3,
            DocumentEntry::class => 10,
        ]);

        $queries->flush();

        // Test (hot)
        $queries  = $this->getQueryLog();
        $importer = $this->app->make(Container::class)
            ->make(CustomerLoader::class)
            ->setWithAssets(CustomerLoaderCreateWithAssets::ASSETS)
            ->setWithAssetsDocuments(CustomerLoaderCreateWithAssets::ASSETS);

        $importer->create(CustomerLoaderCreateWithAssets::CUSTOMER);

        $actual   = $this->cleanupQueryLog($queries->get());
        $expected = $this->getTestData()->json('~create-with-assets-hot.json');

        $this->assertCount(count($expected), $actual);
        $this->assertEquals($expected, $actual);

        $queries->flush();
    }

    public function testCreateWithWarrantyCheck(): void {
        $this->override(Client::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('triggerCoverageStatusCheck')
                ->once()
                ->andReturn(false);
            $mock
                ->shouldReceive('call')
                ->never();
        });

        $id     = $this->faker->uuid;
        $loader = $this->app->make(Container::class)
            ->make(CustomerLoader::class)
            ->setWithWarrantyCheck(true);

        $this->expectExceptionObject(new CustomerWarrantyCheckFailed($id));

        $loader->create($id);
    }
}
