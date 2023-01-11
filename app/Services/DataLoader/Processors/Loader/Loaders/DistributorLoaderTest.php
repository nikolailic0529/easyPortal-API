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
use Tests\Data\Services\DataLoader\Loaders\DistributorLoaderData;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @covers \App\Services\DataLoader\Processors\Loader\Loader
 * @covers \App\Services\DataLoader\Processors\Loader\Loaders\DistributorLoader
 */
class DistributorLoaderTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    public function testProcess(): void {
        // Generate
        $this->generateData(DistributorLoaderData::class);

        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('d3f06a69-43c9-497e-b033-f0928f757126');

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

        $this->app->make(DistributorLoader::class)
            ->setObjectId(DistributorLoaderData::DISTRIBUTOR)
            ->start();

        self::assertQueryLogEquals('~process-cold-queries.json', $queries);
        self::assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 0,
            Customer::class      => 0,
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

        $this->app->make(DistributorLoader::class)
            ->setObjectId(DistributorLoaderData::DISTRIBUTOR)
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
        $this->generateData(DistributorLoaderData::class);

        // Prepare
        $distributor = Distributor::factory()->create([
            'id' => DistributorLoaderData::DISTRIBUTOR,
        ]);

        self::assertTrue($distributor->delete());
        self::assertTrue($distributor->trashed());

        // Pretest
        self::assertModelsCount([
            Distributor::class => 0,
        ]);

        // Test
        $this->app->make(DistributorLoader::class)
            ->setObjectId(DistributorLoaderData::DISTRIBUTOR)
            ->start();

        self::assertModelsCount([
            Distributor::class => 1,
        ]);
    }
}
