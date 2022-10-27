<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Importers;

use App\Services\DataLoader\Processors\Importer\Importers\Customers\AssetsImporter;
use App\Services\DataLoader\Testing\Data\AssetsData;
use App\Services\DataLoader\Testing\Data\Context;
use LastDragon_ru\LaraASP\Testing\Utils\TestData;

class CustomerAssetsImporterData extends AssetsData {
    public const LIMIT    = 5;
    public const CUSTOMER = '019a3b56-b701-4599-8452-2cf9f1f54b26';

    protected function generateData(TestData $root, Context $context): bool {
        return $this->app->make(AssetsImporter::class)
            ->setObjectId(static::CUSTOMER)
            ->setChunkSize(static::CHUNK)
            ->setLimit(static::LIMIT)
            ->start();
    }
}
