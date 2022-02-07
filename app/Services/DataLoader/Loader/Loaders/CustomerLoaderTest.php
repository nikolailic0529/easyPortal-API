<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader\Loaders;

use App\Models\Asset;
use App\Models\AssetWarranty;
use App\Models\Customer;
use App\Models\Distributor;
use App\Models\Document;
use App\Models\DocumentEntry;
use App\Models\Reseller;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Events\DataImported;
use App\Services\DataLoader\Exceptions\CustomerWarrantyCheckFailed;
use App\Services\DataLoader\Testing\Helper;
use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;
use Tests\Data\Services\DataLoader\Loaders\CustomerLoaderCreateWithAssets;
use Tests\Data\Services\DataLoader\Loaders\CustomerLoaderCreateWithoutAssets;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Loader\Loaders\CustomerLoader
 */
class CustomerLoaderTest extends TestCase {
    use WithQueryLogs;
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
            Reseller::class      => 1,
            Customer::class      => 0,
            Asset::class         => 0,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);

        // Test (cold)
        $events   = Event::fake(DataImported::class);
        $queries  = $this->getQueryLog();
        $importer = $this->app->make(Container::class)
            ->make(CustomerLoader::class)
            ->setWithAssets(CustomerLoaderCreateWithoutAssets::ASSETS)
            ->setWithAssetsDocuments(CustomerLoaderCreateWithoutAssets::ASSETS);

        $importer->create(CustomerLoaderCreateWithoutAssets::CUSTOMER);

        $this->assertQueryLogEquals('~create-without-assets-cold.json', $queries);
        $this->assertModelsCount([
            Distributor::class   => 0,
            Reseller::class      => 1,
            Customer::class      => 1,
            Asset::class         => 0,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);
        $this->assertDispatchedEventsEquals(
            '~create-without-assets-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);

        // Test (hot)
        $events   = Event::fake(DataImported::class);
        $queries  = $this->getQueryLog();
        $importer = $this->app->make(Container::class)
            ->make(CustomerLoader::class)
            ->setWithAssets(CustomerLoaderCreateWithoutAssets::ASSETS)
            ->setWithAssetsDocuments(CustomerLoaderCreateWithoutAssets::ASSETS);

        $importer->create(CustomerLoaderCreateWithoutAssets::CUSTOMER);

        $this->assertQueryLogEquals('~create-without-assets-hot.json', $queries);
        $this->assertDispatchedEventsEquals(
            '~create-without-assets-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);
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
            Reseller::class      => 6,
            Customer::class      => 2,
            Asset::class         => 1,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);

        // Test (cold)
        $events   = Event::fake(DataImported::class);
        $queries  = $this->getQueryLog();
        $importer = $this->app->make(Container::class)
            ->make(CustomerLoader::class)
            ->setWithAssets(CustomerLoaderCreateWithAssets::ASSETS)
            ->setWithAssetsDocuments(CustomerLoaderCreateWithAssets::ASSETS);

        $importer->create(CustomerLoaderCreateWithAssets::CUSTOMER);

        $this->assertQueryLogEquals('~create-with-assets-cold.json', $queries);
        $this->assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 6,
            Customer::class      => 2,
            Asset::class         => 3,
            AssetWarranty::class => 12,
            Document::class      => 5,
            DocumentEntry::class => 16,
        ]);
        $this->assertDispatchedEventsEquals(
            '~create-with-assets-cold-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);

        // Test (hot)
        $events   = Event::fake(DataImported::class);
        $queries  = $this->getQueryLog();
        $importer = $this->app->make(Container::class)
            ->make(CustomerLoader::class)
            ->setWithAssets(CustomerLoaderCreateWithAssets::ASSETS)
            ->setWithAssetsDocuments(CustomerLoaderCreateWithAssets::ASSETS);

        $importer->create(CustomerLoaderCreateWithAssets::CUSTOMER);

        $this->assertQueryLogEquals('~create-with-assets-hot.json', $queries);
        $this->assertDispatchedEventsEquals(
            '~create-with-assets-hot-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);
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
