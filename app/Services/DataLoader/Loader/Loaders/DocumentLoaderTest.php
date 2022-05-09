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
use Tests\Data\Services\DataLoader\Loaders\DocumentLoaderCreate;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Loader\Loaders\DocumentLoader
 */
class DocumentLoaderTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    /**
     * @covers ::create
     */
    public function testCreate(): void {
        // Generate
        $this->generateData(DocumentLoaderCreate::class);

        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('ae9b555a-9328-497a-bd3f-ce0e3ec15081');

        // Pretest
        self::assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 4,
            Customer::class      => 1,
            Asset::class         => 0,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);

        // Test (cold)
        $events  = Event::fake(DataImported::class);
        $queries = $this->getQueryLog();

        $this->app->make(DocumentLoader::class)
            ->setObjectId(DocumentLoaderCreate::DOCUMENT)
            ->start();

        self::assertQueryLogEquals('~create-cold.json', $queries);
        self::assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 4,
            Customer::class      => 1,
            Asset::class         => 96,
            AssetWarranty::class => 0,
            Document::class      => 1,
            DocumentEntry::class => 96,
        ]);
        self::assertDispatchedEventsEquals(
            '~create-cold-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);

        // Test (hot)
        $events  = Event::fake(DataImported::class);
        $queries = $this->getQueryLog();

        $this->app->make(DocumentLoader::class)
            ->setObjectId(DocumentLoaderCreate::DOCUMENT)
            ->start();

        self::assertQueryLogEquals('~create-hot.json', $queries);
        self::assertDispatchedEventsEquals(
            '~create-hot-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);
    }
}
