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
use App\Services\DataLoader\Testing\Helper;
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
        $queries  = $this->getQueryLog();
        $importer = $this->app->make(Container::class)
            ->make(DistributorLoader::class);

        $importer->create(DistributorLoaderCreate::DISTRIBUTOR);

        $this->assertQueryLogEquals('~create-cold.json', $queries);
        $this->assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 0,
            Customer::class      => 0,
            Asset::class         => 0,
            AssetWarranty::class => 0,
            Document::class      => 0,
            DocumentEntry::class => 0,
        ]);

        $queries->flush();

        // Test (hot)
        $queries  = $this->getQueryLog();
        $importer = $this->app->make(Container::class)
            ->make(DistributorLoader::class);

        $importer->create(DistributorLoaderCreate::DISTRIBUTOR);

        $this->assertQueryLogEquals('~create-hot.json', $queries);

        $queries->flush();
    }
}
