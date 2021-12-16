<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders;

use App\Models\Asset;
use App\Models\AssetWarranty;
use App\Models\Customer;
use App\Models\Distributor;
use App\Models\Document;
use App\Models\DocumentEntry;
use App\Models\Reseller;
use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Testing\Helper;
use Tests\Data\Services\DataLoader\Loaders\DocumentLoaderCreate;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Loaders\DocumentLoader
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
        $importer = $this->app->make(Container::class)
            ->make(DocumentLoader::class);

        $importer->create(DocumentLoaderCreate::DOCUMENT);

        $this->assertQueryLogEquals('~create-cold.json', $queries);
        $this->assertModelsCount([
            Distributor::class   => 1,
            Reseller::class      => 1,
            Customer::class      => 1,
            Asset::class         => 0,
            AssetWarranty::class => 0,
            Document::class      => 1,
            DocumentEntry::class => 0,
        ]);

        $queries->flush();

        // Test (hot)
        $queries  = $this->getQueryLog();
        $importer = $this->app->make(Container::class)
            ->make(DocumentLoader::class);

        $importer->create(DocumentLoaderCreate::DOCUMENT);

        $this->assertQueryLogEquals('~create-hot.json', $queries);

        $queries->flush();
    }
}
