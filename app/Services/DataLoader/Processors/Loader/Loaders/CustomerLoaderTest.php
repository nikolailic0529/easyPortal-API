<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Loader\Loaders;

use App\Models\Asset;
use App\Models\AssetWarranty;
use App\Models\Customer;
use App\Models\Distributor;
use App\Models\Document;
use App\Models\DocumentEntry;
use App\Models\Reseller;
use App\Services\DataLoader\Events\DataImported;
use App\Services\DataLoader\Testing\Helper;
use Illuminate\Support\Facades\Event;
use Tests\Data\Services\DataLoader\Loaders\CustomerLoaderData;
use Tests\Data\Services\DataLoader\Loaders\CustomerLoaderDataWithAssets;
use Tests\Data\Services\DataLoader\Loaders\CustomerLoaderDataWithDocuments;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @covers \App\Services\DataLoader\Processors\Loader\Loaders\CustomerLoader
 */
class CustomerLoaderTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    public function testProcess(): void {
        // Generate
        $this->generateData(CustomerLoaderData::class);

        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('0b63533a-713b-4a6b-b49c-849612feb478');

        // Pretest
        self::assertModelsCount([
            Distributor::class   => 0,
            Reseller::class      => 0,
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
            ->setObjectId(CustomerLoaderData::CUSTOMER)
            ->setWithAssets(CustomerLoaderData::ASSETS)
            ->start();

        self::assertQueryLogEquals('~process-cold-queries.json', $queries);
        self::assertModelsCount([
            Distributor::class   => 0,
            Reseller::class      => 0,
            Customer::class      => 1,
            Asset::class         => 0,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);
        self::assertDispatchedEventsEquals(
            '~process-cold-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);

        // Test (hot)
        $events  = Event::fake(DataImported::class);
        $queries = $this->getQueryLog();

        $this->app->make(CustomerLoader::class)
            ->setObjectId(CustomerLoaderData::CUSTOMER)
            ->setWithAssets(CustomerLoaderData::ASSETS)
            ->start();

        self::assertQueryLogEquals('~process-hot-queries.json', $queries);
        self::assertDispatchedEventsEquals(
            '~process-hot-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);
    }

    public function testProcessWithAssets(): void {
        // Generate
        $this->generateData(CustomerLoaderDataWithAssets::class);

        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('95da4f51-001a-4f13-a12e-9723127ae0d0');

        // Pretest
        self::assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 2,
            Customer::class      => 2,
            Asset::class         => 2,
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
            ->start();

        self::assertQueryLogEquals('~process-with-assets-cold-queries.json', $queries);
        self::assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 2,
            Customer::class      => 2,
            Asset::class         => 11,
            AssetWarranty::class => 12,
            Document::class      => 0,
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
            ->start();

        self::assertQueryLogEquals('~process-with-assets-hot-queries.json', $queries);
        self::assertDispatchedEventsEquals(
            '~process-with-assets-hot-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);
    }

    public function testProcessWithDocuments(): void {
        // Generate
        $this->generateData(CustomerLoaderDataWithDocuments::class);

        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('7b44f110-3c33-4c0a-a9a8-e1fdaef4e012');

        // Pretest
        self::assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 2,
            Customer::class      => 3,
            Asset::class         => 3,
            AssetWarranty::class => 2,
            Document::class      => 2,
            DocumentEntry::class => 0,
        ]);

        // Test (cold)
        $events  = Event::fake(DataImported::class);
        $queries = $this->getQueryLog();

        $this->app->make(CustomerLoader::class)
            ->setObjectId(CustomerLoaderDataWithDocuments::CUSTOMER)
            ->setWithDocuments(CustomerLoaderDataWithDocuments::DOCUMENTS)
            ->start();

        self::assertQueryLogEquals('~process-with-documents-cold-queries.json', $queries);
        self::assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 2,
            Customer::class      => 3,
            Asset::class         => 3,
            AssetWarranty::class => 2,
            Document::class      => 4,
            DocumentEntry::class => 11,
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

        self::assertQueryLogEquals('~process-with-documents-hot-queries.json', $queries);
        self::assertDispatchedEventsEquals(
            '~process-with-documents-hot-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);
    }

    public function testProcessTrashed(): void {
        // Generate
        $this->generateData(CustomerLoaderData::class);

        // Prepare
        $customer = Customer::factory()->create([
            'id' => CustomerLoaderData::CUSTOMER,
        ]);

        self::assertTrue($customer->delete());
        self::assertTrue($customer->trashed());

        // Pretest
        self::assertModelsCount([
            Customer::class => 0,
        ]);

        // Test
        $this->app->make(CustomerLoader::class)
            ->setObjectId(CustomerLoaderData::CUSTOMER)
            ->setWithAssets(CustomerLoaderData::ASSETS)
            ->start();

        self::assertModelsCount([
            Customer::class => 1,
        ]);
    }
}
