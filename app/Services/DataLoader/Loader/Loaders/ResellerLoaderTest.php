<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader\Loaders;

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
use Tests\Data\Services\DataLoader\Loaders\ResellerLoaderDataWithAssets;
use Tests\Data\Services\DataLoader\Loaders\ResellerLoaderDataWithDocuments;
use Tests\Data\Services\DataLoader\Loaders\ResellerLoaderDataWithoutAssets;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Loader\Loaders\ResellerLoader
 */
class ResellerLoaderTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    /**
     * @covers ::process
     */
    public function testProcessWithoutAssets(): void {
        // Generate
        $this->generateData(ResellerLoaderDataWithoutAssets::class);

        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('9032508d-8f14-46b5-a5a1-3aaad383e787');

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

        $this->app->make(ResellerLoader::class)
            ->setObjectId(ResellerLoaderDataWithoutAssets::RESELLER)
            ->setWithAssets(ResellerLoaderDataWithoutAssets::ASSETS)
            ->setWithAssetsDocuments(ResellerLoaderDataWithoutAssets::ASSETS)
            ->start();

        self::assertQueryLogEquals('~process-without-assets-cold-queries.json', $queries);
        self::assertModelsCount([
            Distributor::class   => 0,
            Reseller::class      => 1,
            Customer::class      => 0,
            Asset::class         => 0,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);
        self::assertDispatchedEventsEquals(
            '~process-without-assets-cold-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);

        // Test (hot)
        $events  = Event::fake(DataImported::class);
        $queries = $this->getQueryLog();

        $this->app->make(ResellerLoader::class)
            ->setObjectId(ResellerLoaderDataWithoutAssets::RESELLER)
            ->setWithAssets(ResellerLoaderDataWithoutAssets::ASSETS)
            ->setWithAssetsDocuments(ResellerLoaderDataWithoutAssets::ASSETS)
            ->start();

        self::assertQueryLogEquals('~process-without-assets-hot-queries.json', $queries);
        self::assertDispatchedEventsEquals(
            '~process-without-assets-hot-events.json',
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
        $this->generateData(ResellerLoaderDataWithAssets::class);

        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('b6545003-2b27-4a62-9309-fdf6af8949d8');

        // Pretest
        self::assertModelsCount([
            Distributor::class   => 2,
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

        $this->app->make(ResellerLoader::class)
            ->setObjectId(ResellerLoaderDataWithAssets::RESELLER)
            ->setWithAssets(ResellerLoaderDataWithAssets::ASSETS)
            ->setWithAssetsDocuments(ResellerLoaderDataWithAssets::ASSETS)
            ->start();

        self::assertQueryLogEquals('~process-with-assets-cold-queries.json', $queries);
        self::assertModelsCount([
            Distributor::class   => 2,
            Reseller::class      => 2,
            Customer::class      => 2,
            Asset::class         => 73,
            AssetWarranty::class => 120,
            Document::class      => 2,
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

        $this->app->make(ResellerLoader::class)
            ->setObjectId(ResellerLoaderDataWithAssets::RESELLER)
            ->setWithAssets(ResellerLoaderDataWithAssets::ASSETS)
            ->setWithAssetsDocuments(ResellerLoaderDataWithAssets::ASSETS)
            ->start();

        self::assertQueryLogEquals('~process-with-assets-hot-queries.json', $queries);
        self::assertDispatchedEventsEquals(
            '~process-with-assets-hot-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);
    }

    /**
     * @covers ::handle
     */
    public function testProcessWithDocuments(): void {
        // Generate
        $this->generateData(ResellerLoaderDataWithDocuments::class);

        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('7b44f110-3c33-4c0a-a9a8-e1fdaef4e012');

        // Pretest
        self::assertModelsCount([
            Distributor::class   => 2,
            Reseller::class      => 2,
            Customer::class      => 2,
            Asset::class         => 8,
            AssetWarranty::class => 22,
            Document::class      => 5,
            DocumentEntry::class => 0,
        ]);

        // Test (cold)
        $events  = Event::fake(DataImported::class);
        $queries = $this->getQueryLog();

        $this->app->make(ResellerLoader::class)
            ->setObjectId(ResellerLoaderDataWithDocuments::RESELLER)
            ->setWithDocuments(ResellerLoaderDataWithDocuments::DOCUMENTS)
            ->start();

        self::assertQueryLogEquals('~process-with-documents-cold-queries.json', $queries);
        self::assertModelsCount([
            Distributor::class   => 2,
            Reseller::class      => 2,
            Customer::class      => 2,
            Asset::class         => 8,
            AssetWarranty::class => 22,
            Document::class      => 4,
            DocumentEntry::class => 5,
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

        $this->app->make(ResellerLoader::class)
            ->setObjectId(ResellerLoaderDataWithDocuments::RESELLER)
            ->setWithDocuments(ResellerLoaderDataWithDocuments::DOCUMENTS)
            ->start();

        self::assertQueryLogEquals('~process-with-documents-hot-queries.json', $queries);
        self::assertDispatchedEventsEquals(
            '~process-with-documents-hot-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);
    }

    /**
     * @covers ::process
     */
    public function testProcessTrashed(): void {
        // Generate
        $this->generateData(ResellerLoaderDataWithoutAssets::class);

        // Prepare
        $reseller = Reseller::factory()->create([
            'id' => ResellerLoaderDataWithoutAssets::RESELLER,
        ]);

        self::assertTrue($reseller->delete());
        self::assertTrue($reseller->trashed());

        // Pretest
        self::assertModelsCount([
            Reseller::class => 0,
        ]);

        // Test
        $this->app->make(ResellerLoader::class)
            ->setObjectId(ResellerLoaderDataWithoutAssets::RESELLER)
            ->setWithAssets(ResellerLoaderDataWithoutAssets::ASSETS)
            ->setWithAssetsDocuments(ResellerLoaderDataWithoutAssets::DOCUMENTS)
            ->start();

        self::assertModelsCount([
            Reseller::class => 1,
        ]);
    }
}
