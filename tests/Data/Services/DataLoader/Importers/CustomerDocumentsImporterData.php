<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Importers;

use App\Services\DataLoader\Processors\Importer\Importers\Customers\DocumentsImporter;
use App\Services\DataLoader\Testing\Data\Context;
use App\Services\DataLoader\Testing\Data\DocumentsData;

class CustomerDocumentsImporterData extends DocumentsData {
    public const CUSTOMER = '3adf6426-735c-4f52-9f28-e3ad593e707c';

    protected function generateData(string $path, Context $context): bool {
        return $this->app->make(DocumentsImporter::class)
            ->setObjectId(static::CUSTOMER)
            ->setChunkSize(static::CHUNK)
            ->setLimit(static::LIMIT)
            ->start();
    }
}
