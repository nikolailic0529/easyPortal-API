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
use Tests\Data\Services\DataLoader\Loaders\AssetLoaderDataWithDocuments;
use Tests\Data\Services\DataLoader\Loaders\AssetLoaderDataWithoutDocuments;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Loader\Loaders\AssetLoader
 */
class AssetLoaderTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    /**
     * @covers ::process
     */
    public function testProcessWithoutDocuments(): void {
        // Generate
        $this->generateData(AssetLoaderDataWithoutDocuments::class);

        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('d9de1374-a2c9-42c3-b00f-cdcf486180b1');

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
            ->setObjectId(AssetLoaderDataWithoutDocuments::ASSET)
            ->setWithDocuments(AssetLoaderDataWithoutDocuments::DOCUMENTS)
            ->start();

        self::assertQueryLogEquals('~process-without-documents-cold-queries.json', $queries);
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
            '~process-without-documents-cold-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);

        // Test (hot)
        $events  = Event::fake(DataImported::class);
        $queries = $this->getQueryLog();

        $this->app->make(AssetLoader::class)
            ->setObjectId(AssetLoaderDataWithoutDocuments::ASSET)
            ->setWithDocuments(AssetLoaderDataWithoutDocuments::DOCUMENTS)
            ->start();

        self::assertQueryLogEquals('~process-without-documents-hot-queries.json', $queries);
        self::assertDispatchedEventsEquals(
            '~process-without-documents-hot-events.json',
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
        $this->generateData(AssetLoaderDataWithDocuments::class);

        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('8cab7372-67cf-41c8-8aa4-3e25a3b293a2');

        // Pretest
        self::assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 1,
            Customer::class      => 2,
            Asset::class         => 0,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);

        // Test (cold)
        $events  = Event::fake(DataImported::class);
        $queries = $this->getQueryLog();

        $this->app->make(AssetLoader::class)
            ->setObjectId(AssetLoaderDataWithDocuments::ASSET)
            ->setWithDocuments(AssetLoaderDataWithDocuments::DOCUMENTS)
            ->start();

        self::assertQueryLogEquals('~process-with-documents-cold-queries.json', $queries);
        self::assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 1,
            Customer::class      => 2,
            Asset::class         => 1,
            AssetWarranty::class => 0,
            Document::class      => 3,
            DocumentEntry::class => 0,
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

        $this->app->make(AssetLoader::class)
            ->setObjectId(AssetLoaderDataWithDocuments::ASSET)
            ->setWithDocuments(AssetLoaderDataWithDocuments::DOCUMENTS)
            ->start();

        self::assertQueryLogEquals('~process-with-documents-hot-queries.json', $queries);
        self::assertDispatchedEventsEquals(
            '~process-with-documents-hot-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);
    }
}
