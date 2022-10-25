<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Importers;

use App\Services\DataLoader\Processors\Importer\Importers\Resellers\AssetsImporter;
use App\Services\DataLoader\Testing\Data\AssetsData;
use App\Services\DataLoader\Testing\Data\Context;

class ResellerAssetsImporterData extends AssetsData {
    public const RESELLER  = '27faa47d-ab2a-4755-b36b-729114c056d2';
    public const DOCUMENTS = true;

    protected function generateData(string $path, Context $context): bool {
        return $this->app->make(AssetsImporter::class)
            ->setObjectId(static::RESELLER)
            ->setChunkSize(static::CHUNK)
            ->setLimit(static::LIMIT)
            ->start();
    }
}
