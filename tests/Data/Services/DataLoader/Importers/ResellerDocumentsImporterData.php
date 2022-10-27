<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Importers;

use App\Services\DataLoader\Processors\Importer\Importers\Resellers\DocumentsImporter;
use App\Services\DataLoader\Testing\Data\Context;
use App\Services\DataLoader\Testing\Data\DocumentsData;
use LastDragon_ru\LaraASP\Testing\Utils\TestData;

class ResellerDocumentsImporterData extends DocumentsData {
    public const RESELLER = '27faa47d-ab2a-4755-b36b-729114c056d2';

    protected function generateData(TestData $root, Context $context): bool {
        return $this->app->make(DocumentsImporter::class)
            ->setObjectId(static::RESELLER)
            ->setChunkSize(static::CHUNK)
            ->setLimit(static::LIMIT)
            ->start();
    }
}
