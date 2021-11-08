<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders;

use App\Models\Asset;
use App\Models\AssetWarranty;
use App\Models\Customer;
use App\Models\Distributor;
use App\Models\Document;
use App\Models\DocumentEntry;
use App\Models\Reseller;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Exceptions\AssetWarrantyCheckFailed;
use App\Services\DataLoader\Schema\TriggerCoverageStatusCheck;
use App\Services\DataLoader\Testing\Helper;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Mockery\MockInterface;
use Tests\Data\Services\DataLoader\Loaders\AssetLoaderCreateWithDocuments;
use Tests\Data\Services\DataLoader\Loaders\AssetLoaderCreateWithoutDocuments;
use Tests\TestCase;

use function count;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Loaders\AssetLoader
 */
class AssetLoaderTest extends TestCase {
    use WithQueryLog;
    use Helper;

    /**
     * @covers ::create
     */
    public function testCreateWithoutDocuments(): void {
        // Generate
        $this->generateData(AssetLoaderCreateWithoutDocuments::class);

        // Pretest
        $this->assertModelsCount([
            Distributor::class   => 0,
            Reseller::class      => 0,
            Customer::class      => 1,
            Asset::class         => 0,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);

        // Test (cold)
        $queries  = $this->getQueryLog();
        $importer = $this->app->make(AssetLoader::class)
            ->setWithDocuments(AssetLoaderCreateWithoutDocuments::DOCUMENTS);

        $importer->create(AssetLoaderCreateWithoutDocuments::ASSET);

        $actual   = $this->cleanupQueryLog($queries->get());
        $expected = $this->getTestData()->json('~create-without-documents.json');

        $this->assertCount(count($expected), $actual);
        $this->assertEquals($expected, $actual);
        $this->assertModelsCount([
            Distributor::class   => 0,
            Reseller::class      => 0,
            Customer::class      => 1,
            Asset::class         => 1,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);

        $queries->flush();
    }

    /**
     * @covers ::handle
     */
    public function testCreateWithDocuments(): void {
        // Generate
        $this->generateData(AssetLoaderCreateWithDocuments::class);

        // Pretest
        $this->assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 1,
            Customer::class      => 1,
            Asset::class         => 0,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);

        // Test (cold)
        $queries  = $this->getQueryLog();
        $importer = $this->app->make(AssetLoader::class)
            ->setWithDocuments(AssetLoaderCreateWithDocuments::DOCUMENTS);

        $importer->create(AssetLoaderCreateWithDocuments::ASSET);

        $actual   = $this->cleanupQueryLog($queries->get());
        $expected = $this->getTestData()->json('~create-with-documents.json');

        $this->assertCount(count($expected), $actual);
        $this->assertEquals($expected, $actual);
        $this->assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 1,
            Customer::class      => 1,
            Asset::class         => 1,
            AssetWarranty::class => 6,
            Document::class      => 4,
            DocumentEntry::class => 16,
        ]);

        $queries->flush();
    }

    public function testCreateWithWarrantyCheck(): void {
        $this->override(Client::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('triggerCoverageStatusCheck')
                ->once()
                ->andReturn(false);
            $mock
                ->shouldReceive('call')
                ->never();
        });

        $id     = $this->faker->uuid;
        $loader = $this->app->make(AssetLoader::class)->setWithWarrantyCheck(true);

        $this->expectExceptionObject(new AssetWarrantyCheckFailed($id));

        $loader->create($id);
    }
}
