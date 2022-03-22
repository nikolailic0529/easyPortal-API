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
use Tests\Data\Services\DataLoader\Loaders\DistributorLoaderCreate;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Loader\Loaders\DistributorLoader
 */
class DistributorLoaderTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    /**
     * @covers ::create
     */
    public function testCreate(): void {
        // Generate
        $this->generateData(DistributorLoaderCreate::class);

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
        $events   = Event::fake(DataImported::class);
        $queries  = $this->getQueryLog();
        $importer = $this->app->make(Container::class)
            ->make(DistributorLoader::class);

        $importer->create(DistributorLoaderCreate::DISTRIBUTOR);

        self::assertQueryLogEquals('~create-cold.json', $queries);
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
            '~create-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);

        // Test (hot)
        $events   = Event::fake(DataImported::class);
        $queries  = $this->getQueryLog();
        $importer = $this->app->make(Container::class)
            ->make(DistributorLoader::class);

        $importer->create(DistributorLoaderCreate::DISTRIBUTOR);

        self::assertQueryLogEquals('~create-hot.json', $queries);
        self::assertDispatchedEventsEquals(
            '~create-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);
    }
}
