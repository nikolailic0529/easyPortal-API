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
use Tests\Data\Services\DataLoader\Loaders\DocumentLoaderData;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @covers \App\Services\DataLoader\Processors\Loader\Loader
 * @covers \App\Services\DataLoader\Processors\Loader\Loaders\DocumentLoader
 */
class DocumentLoaderTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    public function testProcess(): void {
        // Generate
        $this->generateData(DocumentLoaderData::class);

        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('ae9b555a-9328-497a-bd3f-ce0e3ec15081');

        // Pretest
        self::assertModelsCount([
            Distributor::class   => 1,
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

        $this->app->make(DocumentLoader::class)
            ->setObjectId(DocumentLoaderData::DOCUMENT)
            ->start();

        self::assertQueryLogEquals('~process-cold-queries.json', $queries);
        self::assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 1,
            Customer::class      => 1,
            Asset::class         => 4,
            AssetWarranty::class => 0,
            Document::class      => 1,
            DocumentEntry::class => 4,
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

        $this->app->make(DocumentLoader::class)
            ->setObjectId(DocumentLoaderData::DOCUMENT)
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
        $this->generateData(DocumentLoaderData::class);

        // Prepare
        $document = Document::factory()->create([
            'id' => DocumentLoaderData::DOCUMENT,
        ]);

        self::assertTrue($document->delete());
        self::assertTrue($document->trashed());

        // Pretest
        self::assertModelsCount([
            Document::class => 0,
        ]);

        // Test
        $this->app->make(DocumentLoader::class)
            ->setObjectId(DocumentLoaderData::DOCUMENT)
            ->start();

        self::assertModelsCount([
            Document::class => 1,
        ]);
    }
}
