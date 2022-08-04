<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Importers;

use App\Services\DataLoader\Importer\Importers\Customers\AssetsImporter;
use App\Services\DataLoader\Testing\Data\AssetsData;

class CustomerAssetsImporterDataWithoutDocuments extends AssetsData {
    public const CUSTOMER  = '8e57be47-2088-48c7-9342-0a2c64293248';
    public const DOCUMENTS = false;

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
