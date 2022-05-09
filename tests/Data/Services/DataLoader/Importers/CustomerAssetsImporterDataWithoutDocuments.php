<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Importers;

use App\Services\DataLoader\Importer\Importers\Customers\AssetsImporter;
use App\Services\DataLoader\Testing\Data\AssetsData;

class CustomerAssetsImporterDataWithoutDocuments extends AssetsData {
    public const CUSTOMER  = '45e660e4-a10d-49a5-ab47-d9e2c8aa44fe';
    public const DOCUMENTS = false;
    public const LIMIT     = 50;
    public const CHUNK     = 10;

    protected function generateData(string $path): bool {
        return $this->dumpClientResponses($path, function (): bool {
            $this->app->make(AssetsImporter::class)
                ->setObjectId(static::CUSTOMER)
                ->setWithDocuments(static::DOCUMENTS)
                ->setChunkSize(static::CHUNK)
                ->setLimit(static::LIMIT)
                ->start();

            return true;
        });
    }
}
