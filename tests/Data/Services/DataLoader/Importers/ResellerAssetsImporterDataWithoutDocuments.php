<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Importers;

use App\Services\DataLoader\Importer\Importers\ResellerAssetsImporter;
use App\Services\DataLoader\Testing\Data\AssetsData;

class ResellerAssetsImporterDataWithoutDocuments extends AssetsData {
    public const RESELLER  = '8c248080-ad4f-4dbd-8310-6afb07f67a42';
    public const DOCUMENTS = false;
    public const LIMIT     = 50;
    public const CHUNK     = 10;

    protected function generateData(string $path): bool {
        return $this->dumpClientResponses($path, function (): bool {
            $this->app->make(ResellerAssetsImporter::class)
                ->setResellerId(static::RESELLER)
                ->setWithDocuments(static::DOCUMENTS)
                ->setChunkSize(static::CHUNK)
                ->setLimit(static::LIMIT)
                ->start();

            return true;
        });
    }
}
