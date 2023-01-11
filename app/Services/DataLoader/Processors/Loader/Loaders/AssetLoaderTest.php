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
use Tests\Data\Services\DataLoader\Loaders\AssetLoaderData;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @covers \App\Services\DataLoader\Processors\Loader\Loader
 * @covers \App\Services\DataLoader\Processors\Loader\Loaders\AssetLoader
 */
class AssetLoaderTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    public function testProcess(): void {
        // Generate
        $this->generateData(AssetLoaderData::class);

        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('8cab7372-67cf-41c8-8aa4-3e25a3b293a2');

        // Pretest
        self::assertModelsCount([
            Distributor::class   => 0,
            Reseller::class      => 1,
            Customer::class      => 1,
            Asset::class         => 0,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);

        // Test (cold)
        $events  = Event::fake(DataImported::class);
        $queries = $this->getQueryLog();

        $this->app->make(AssetLoader::class)
            ->setObjectId(AssetLoaderData::ASSET)
            ->start();

        self::assertQueryLogEquals('~process-cold-queries.json', $queries);
        self::assertModelsCount([
            Distributor::class   => 0,
            Reseller::class      => 1,
            Customer::class      => 1,
            Asset::class         => 1,
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

        $this->app->make(AssetLoader::class)
            ->setObjectId(AssetLoaderData::ASSET)
            ->start();

        self::assertQueryLogEquals('~process-hot-queries.json', $queries);
        self::assertDispatchedEventsEquals(
            '~process-hot-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);
    }

    public function testProcessTrashed(): void {
        // Generate
        $this->generateData(AssetLoaderData::class);

        // Prepare
        $asset = Asset::factory()->create([
            'id' => AssetLoaderData::ASSET,
        ]);

        self::assertTrue($asset->delete());
        self::assertTrue($asset->trashed());

        // Pretest
        self::assertModelsCount([
            Asset::class => 0,
        ]);

        // Test
        $this->app->make(AssetLoader::class)
            ->setObjectId(AssetLoaderData::ASSET)
            ->start();

        self::assertModelsCount([
            Asset::class => 1,
        ]);
    }
}
