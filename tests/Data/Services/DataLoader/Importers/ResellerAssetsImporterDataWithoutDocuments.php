<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Importers;

use App\Services\DataLoader\Importer\Importers\Resellers\AssetsImporter;
use App\Services\DataLoader\Testing\Data\AssetsData;

class ResellerAssetsImporterDataWithoutDocuments extends AssetsData {
    public const RESELLER  = '27faa47d-ab2a-4755-b36b-729114c056d2';
    public const DOCUMENTS = false;

    protected function generateData(string $path): bool {
        return $this->dumpClientResponses($path, function (): bool {
            $this->app->make(AssetsImporter::class)
                ->setObjectId(static::RESELLER)
                ->setWithDocuments(static::DOCUMENTS)
                ->setChunkSize(static::CHUNK)
                ->setLimit(static::LIMIT)
                ->start();

            return true;
        });
    }
}
