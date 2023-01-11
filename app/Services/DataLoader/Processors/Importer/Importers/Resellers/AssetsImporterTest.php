<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Importer\Importers\Resellers;

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
use Tests\Data\Services\DataLoader\Importers\ResellerAssetsImporterData;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @covers \App\Services\DataLoader\Processors\Importer\Importers\Assets\BaseImporter
 * @covers \App\Services\DataLoader\Processors\Importer\Importers\Resellers\AssetsImporter
 */
class AssetsImporterTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    public function testProcess(): void {
        // Generate
        $this->generateData(ResellerAssetsImporterData::class);

        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('8c017603-84cf-49f7-a693-ced728446fc4');

        // Pretest
        self::assertModelsCount([
            Distributor::class   => 0,
            Reseller::class      => 1,
            Customer::class      => 4,
            Asset::class         => 0,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);

        // Test (cold)
        $queries = $this->getQueryLog();
        $events  = Event::fake(DataImported::class);

        $this->app->make(AssetsImporter::class)
            ->setObjectId(ResellerAssetsImporterData::RESELLER)
            ->setChunkSize(ResellerAssetsImporterData::CHUNK)
            ->setLimit(ResellerAssetsImporterData::LIMIT)
            ->start();

        self::assertQueryLogEquals('~process-cold-queries.json', $queries);
        self::assertModelsCount([
            Distributor::class   => 0,
            Reseller::class      => 1,
            Customer::class      => 4,
            Asset::class         => 25,
            AssetWarranty::class => 32,
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
        $queries = $this->getQueryLog();
        $events  = Event::fake(DataImported::class);

        $this->app->make(AssetsImporter::class)
            ->setObjectId(ResellerAssetsImporterData::RESELLER)
            ->setChunkSize(ResellerAssetsImporterData::CHUNK)
            ->setLimit(ResellerAssetsImporterData::LIMIT)
            ->start();

        self::assertQueryLogEquals('~process-hot-queries.json', $queries);
        self::assertDispatchedEventsEquals(
            '~process-hot-events.json',
            $events->dispatched(DataImported::class),
        );

        $queries->flush();

        unset($events);
    }
}
