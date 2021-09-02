<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importers;

use App\Models\Asset;
use App\Models\AssetWarranty;
use App\Models\Customer;
use App\Models\Distributor;
use App\Models\Document;
use App\Models\DocumentEntry;
use App\Models\Reseller;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Testing\Data\DataGenerator;
use App\Services\DataLoader\Testing\FakeClient;
use App\Services\DataLoader\Testing\Helper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Tests\Data\Services\DataLoader\Importers\AssetsImporterData;
use Tests\Helpers\SequenceUuidFactory;
use Tests\TestCase;

use function count;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Importers\AssetsImporter
 */
class AssetsImporterTest extends TestCase {
    use WithQueryLog;
    use Helper;

    /**
     * @covers ::import
     */
    public function testImport(): void {
        // Generate
        $this->app->make(DataGenerator::class)->generate(AssetsImporterData::class);

        // Setup
        Date::setTestNow('2021-08-30T00:00:00.000+00:00');
        Str::createUuidsUsing(new SequenceUuidFactory());

        $this->override(Client::class, function (): Client {
            return $this->app->make(FakeClient::class)->setData(AssetsImporterData::class);
        });

        // Restore
        $this->app->make(DataGenerator::class)->restore(AssetsImporterData::class);

        // Pretest
        $this->assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 40,
            Customer::class      => 51,
            Asset::class         => 0,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);

        // Test (cold)
        $queries  = $this->getQueryLog();
        $importer = $this->app->make(AssetsImporter::class);

        $importer->import(true, chunk: AssetsImporterData::CHUNK, limit: AssetsImporterData::LIMIT);

        $actual   = $this->cleanupQueryLog($queries->get());
        $expected = $this->getTestData()->json('~import-cold.json');

        $this->assertCount(count($expected), $actual);
        $this->assertEquals($expected, $actual);
        $this->assertModelsCount([
            Asset::class         => AssetsImporterData::LIMIT,
            AssetWarranty::class => 120,
            Document::class      => 62,
            DocumentEntry::class => 123,
        ]);

        $queries->flush();

        // Test (hot)
        $queries  = $this->getQueryLog();
        $importer = $this->app->make(AssetsImporter::class);

        $importer->import(true, chunk: AssetsImporterData::CHUNK, limit: AssetsImporterData::LIMIT);

        $actual   = $this->cleanupQueryLog($queries->get());
        $expected = $this->getTestData()->json('~import-hot.json');

        $this->assertCount(count($expected), $actual);
        $this->assertEquals($expected, $actual);

        $queries->flush();
    }
}
