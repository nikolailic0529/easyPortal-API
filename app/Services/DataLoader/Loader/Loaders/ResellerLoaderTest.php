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
        $events   = Event::fake(DataImported::class);
        $queries  = $this->getQueryLog();
        $importer = $this->app->make(Container::class)
            ->make(ResellerLoader::class)
            ->setWithAssets(ResellerLoaderCreateWithoutAssets::ASSETS)
            ->setWithAssetsDocuments(ResellerLoaderCreateWithoutAssets::ASSETS);

        $importer->create(ResellerLoaderCreateWithoutAssets::RESELLER);

        $this->assertQueryLogEquals('~create-without-assets-cold.json', $queries);
        $this->assertModelsCount([
            Distributor::class   => 0,
            Reseller::class      => 1,
            Customer::class      => 0,
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
            ->make(ResellerLoader::class)
            ->setWithAssets(ResellerLoaderCreateWithoutAssets::ASSETS)
            ->setWithAssetsDocuments(ResellerLoaderCreateWithoutAssets::ASSETS);

        $importer->create(ResellerLoaderCreateWithoutAssets::RESELLER);

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
        $this->generateData(ResellerLoaderCreateWithAssets::class);

        // Pretest
        $this->assertModelsCount([
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

        $this->assertQueryLogEquals('~create-with-assets-cold.json', $queries);
        $this->assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 4,
            Customer::class      => 2,
            Asset::class         => 6,
            AssetWarranty::class => 11,
            Document::class      => 3,
            DocumentEntry::class => 11,
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
            ->make(ResellerLoader::class)
            ->setWithAssets(ResellerLoaderCreateWithAssets::ASSETS)
            ->setWithAssetsDocuments(ResellerLoaderCreateWithAssets::ASSETS);

        $importer->create(ResellerLoaderCreateWithAssets::RESELLER);

        $this->assertQueryLogEquals('~create-with-assets-hot.json', $queries);
        $this->assertDispatchedEventsEquals(
            '~create-with-assets-hot-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);
    }
}
