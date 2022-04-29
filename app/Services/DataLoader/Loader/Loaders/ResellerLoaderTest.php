<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader\Loaders;

use App\Models\Asset;
use App\Models\AssetWarranty;
use App\Models\Customer;
use App\Models\Distributor;
use App\Models\Document;
use App\Models\DocumentEntry;
use App\Models\Reseller;
use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Events\DataImported;
use App\Services\DataLoader\Testing\Helper;
use Illuminate\Support\Facades\Event;
use Tests\Data\Services\DataLoader\Loaders\ResellerLoaderCreateWithAssets;
use Tests\Data\Services\DataLoader\Loaders\ResellerLoaderCreateWithDocuments;
use Tests\Data\Services\DataLoader\Loaders\ResellerLoaderCreateWithoutAssets;
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
     * @covers ::create
     */
    public function testCreateWithoutAssets(): void {
        // Generate
        $this->generateData(ResellerLoaderCreateWithoutAssets::class);

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
        $events   = Event::fake(DataImported::class);
        $queries  = $this->getQueryLog();
        $importer = $this->app->make(Container::class)
            ->make(ResellerLoader::class)
            ->setWithAssets(ResellerLoaderCreateWithoutAssets::ASSETS)
            ->setWithAssetsDocuments(ResellerLoaderCreateWithoutAssets::ASSETS);

        $importer->create(ResellerLoaderCreateWithoutAssets::RESELLER);

        self::assertQueryLogEquals('~create-without-assets-cold.json', $queries);
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
            '~create-without-assets-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);

        // Test (hot)
        $events   = Event::fake(DataImported::class);
        $queries  = $this->getQueryLog();
        $importer = $this->app->make(Container::class)
            ->make(ResellerLoader::class)
            ->setWithAssets(ResellerLoaderCreateWithoutAssets::ASSETS)
            ->setWithAssetsDocuments(ResellerLoaderCreateWithoutAssets::ASSETS);

        $importer->create(ResellerLoaderCreateWithoutAssets::RESELLER);

        self::assertQueryLogEquals('~create-without-assets-hot.json', $queries);
        self::assertDispatchedEventsEquals(
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
        $this->generateData(ResellerLoaderCreateWithAssets::class);

        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('b6545003-2b27-4a62-9309-fdf6af8949d8');

        // Pretest
        self::assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 4,
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
            ->make(ResellerLoader::class)
            ->setWithAssets(ResellerLoaderCreateWithAssets::ASSETS)
            ->setWithAssetsDocuments(ResellerLoaderCreateWithAssets::ASSETS);

        $importer->create(ResellerLoaderCreateWithAssets::RESELLER);

        self::assertQueryLogEquals('~create-with-assets-cold.json', $queries);
        self::assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 4,
            Customer::class      => 2,
            Asset::class         => 6,
            AssetWarranty::class => 11,
            Document::class      => 3,
            DocumentEntry::class => 0,
        ]);
        self::assertDispatchedEventsEquals(
            '~create-with-assets-cold-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);

        // Test (hot)
        $events   = Event::fake(DataImported::class);
        $queries  = $this->getQueryLog();
        $importer = $this->app->make(Container::class)
            ->make(ResellerLoader::class)
            ->setWithAssets(ResellerLoaderCreateWithAssets::ASSETS)
            ->setWithAssetsDocuments(ResellerLoaderCreateWithAssets::ASSETS);

        $importer->create(ResellerLoaderCreateWithAssets::RESELLER);

        self::assertQueryLogEquals('~create-with-assets-hot.json', $queries);
        self::assertDispatchedEventsEquals(
            '~create-with-assets-hot-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);
    }

    /**
     * @covers ::handle
     */
    public function testCreateWithDocuments(): void {
        // Generate
        $this->generateData(ResellerLoaderCreateWithDocuments::class);

        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('7b44f110-3c33-4c0a-a9a8-e1fdaef4e012');

        // Pretest
        self::assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 2,
            Customer::class      => 5,
            Asset::class         => 8,
            AssetWarranty::class => 1,
            Document::class      => 1,
            DocumentEntry::class => 0,
        ]);

        // Test (cold)
        $events   = Event::fake(DataImported::class);
        $queries  = $this->getQueryLog();
        $importer = $this->app->make(Container::class)
            ->make(ResellerLoader::class)
            ->setWithDocuments(ResellerLoaderCreateWithDocuments::DOCUMENTS);

        $importer->create(ResellerLoaderCreateWithDocuments::RESELLER);

        self::assertQueryLogEquals('~create-with-documents-cold.json', $queries);
        self::assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 2,
            Customer::class      => 5,
            Asset::class         => 8,
            AssetWarranty::class => 1,
            Document::class      => 6,
            DocumentEntry::class => 28,
        ]);
        self::assertDispatchedEventsEquals(
            '~create-with-documents-cold-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);

        // Test (hot)
        $events   = Event::fake(DataImported::class);
        $queries  = $this->getQueryLog();
        $importer = $this->app->make(Container::class)
            ->make(ResellerLoader::class)
            ->setWithDocuments(ResellerLoaderCreateWithDocuments::DOCUMENTS);

        $importer->create(ResellerLoaderCreateWithDocuments::RESELLER);

        self::assertQueryLogEquals('~create-with-documents-hot.json', $queries);
        self::assertDispatchedEventsEquals(
            '~create-with-documents-hot-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);
    }
}
