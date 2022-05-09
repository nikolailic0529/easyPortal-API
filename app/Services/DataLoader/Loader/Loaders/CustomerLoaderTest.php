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
use Tests\Data\Services\DataLoader\Loaders\CustomerLoaderDataWithAssets;
use Tests\Data\Services\DataLoader\Loaders\CustomerLoaderDataWithDocuments;
use Tests\Data\Services\DataLoader\Loaders\CustomerLoaderDataWithoutAssets;
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
     * @covers ::process
     */
    public function testProcessWithoutAssets(): void {
        // Generate
        $this->generateData(CustomerLoaderDataWithoutAssets::class);

        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('0b63533a-713b-4a6b-b49c-849612feb478');

        // Pretest
        self::assertModelsCount([
            Distributor::class   => 0,
            Reseller::class      => 1,
            Customer::class      => 0,
            Asset::class         => 0,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);

        // Test (cold)
        $events  = Event::fake(DataImported::class);
        $queries = $this->getQueryLog();

        $this->app->make(CustomerLoader::class)
            ->setObjectId(CustomerLoaderDataWithoutAssets::CUSTOMER)
            ->setWithAssets(CustomerLoaderDataWithoutAssets::ASSETS)
            ->setWithAssetsDocuments(CustomerLoaderDataWithoutAssets::ASSETS)
            ->start();

        self::assertQueryLogEquals('~process-without-assets-cold.json', $queries);
        self::assertModelsCount([
            Distributor::class   => 0,
            Reseller::class      => 1,
            Customer::class      => 1,
            Asset::class         => 0,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);
        self::assertDispatchedEventsEquals(
            '~process-without-assets-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);

        // Test (hot)
        $events  = Event::fake(DataImported::class);
        $queries = $this->getQueryLog();

        $this->app->make(CustomerLoader::class)
            ->setObjectId(CustomerLoaderDataWithoutAssets::CUSTOMER)
            ->setWithAssets(CustomerLoaderDataWithoutAssets::ASSETS)
            ->setWithAssetsDocuments(CustomerLoaderDataWithoutAssets::ASSETS)
            ->start();

        self::assertQueryLogEquals('~process-without-assets-hot.json', $queries);
        self::assertDispatchedEventsEquals(
            '~process-without-assets-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);
    }

    /**
     * @covers ::handle
     */
    public function testProcessWithAssets(): void {
        // Generate
        $this->generateData(CustomerLoaderDataWithAssets::class);

        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('95da4f51-001a-4f13-a12e-9723127ae0d0');

        // Pretest
        self::assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 6,
            Customer::class      => 2,
            Asset::class         => 1,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);

        // Test (cold)
        $events  = Event::fake(DataImported::class);
        $queries = $this->getQueryLog();

        $this->app->make(CustomerLoader::class)
            ->setObjectId(CustomerLoaderDataWithAssets::CUSTOMER)
            ->setWithAssets(CustomerLoaderDataWithAssets::ASSETS)
            ->setWithAssetsDocuments(CustomerLoaderDataWithAssets::ASSETS)
            ->start();

        self::assertQueryLogEquals('~process-with-assets-cold.json', $queries);
        self::assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 6,
            Customer::class      => 2,
            Asset::class         => 3,
            AssetWarranty::class => 12,
            Document::class      => 5,
            DocumentEntry::class => 0,
        ]);
        self::assertDispatchedEventsEquals(
            '~process-with-assets-cold-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);

        // Test (hot)
        $events  = Event::fake(DataImported::class);
        $queries = $this->getQueryLog();

        $this->app->make(CustomerLoader::class)
            ->setObjectId(CustomerLoaderDataWithAssets::CUSTOMER)
            ->setWithAssets(CustomerLoaderDataWithAssets::ASSETS)
            ->setWithAssetsDocuments(CustomerLoaderDataWithAssets::ASSETS)
            ->start();

        self::assertQueryLogEquals('~process-with-assets-hot.json', $queries);
        self::assertDispatchedEventsEquals(
            '~process-with-assets-hot-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);
    }

    public function testProcessWithWarrantyCheck(): void {
        $this->markTestIncomplete('Not implemented!');

        $this->override(Client::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('runCustomerWarrantyCheck')
                ->once()
                ->andReturn(false);
            $mock
                ->shouldReceive('call')
                ->never();
        });

        $id     = $this->faker->uuid();
        $loader = $this->app->make(Container::class)
            ->make(CustomerLoader::class)
            ->setWithWarrantyCheck(true);

        self::expectExceptionObject(new CustomerWarrantyCheckFailed($id));

        $loader->create($id);
    }

    /**
     * @covers ::handle
     */
    public function testProcessWithDocuments(): void {
        // Generate
        $this->generateData(CustomerLoaderDataWithDocuments::class);

        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('7b44f110-3c33-4c0a-a9a8-e1fdaef4e012');

        // Pretest
        self::assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 5,
            Customer::class      => 3,
            Asset::class         => 9,
            AssetWarranty::class => 0,
            Document::class      => 1,
            DocumentEntry::class => 0,
        ]);

        // Test (cold)
        $events  = Event::fake(DataImported::class);
        $queries = $this->getQueryLog();

        $this->app->make(CustomerLoader::class)
            ->setObjectId(CustomerLoaderDataWithDocuments::CUSTOMER)
            ->setWithDocuments(CustomerLoaderDataWithDocuments::DOCUMENTS)
            ->start();

        self::assertQueryLogEquals('~process-with-documents-cold.json', $queries);
        self::assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 5,
            Customer::class      => 3,
            Asset::class         => 9,
            AssetWarranty::class => 0,
            Document::class      => 16,
            DocumentEntry::class => 132,
        ]);
        self::assertDispatchedEventsEquals(
            '~process-with-documents-cold-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);

        // Test (hot)
        $events  = Event::fake(DataImported::class);
        $queries = $this->getQueryLog();

        $this->app->make(CustomerLoader::class)
            ->setObjectId(CustomerLoaderDataWithDocuments::CUSTOMER)
            ->setWithDocuments(CustomerLoaderDataWithDocuments::DOCUMENTS)
            ->start();

        self::assertQueryLogEquals('~process-with-documents-hot.json', $queries);
        self::assertDispatchedEventsEquals(
            '~process-with-documents-hot-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);
    }
}
