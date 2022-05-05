<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Importers;

use App\Services\DataLoader\Importer\Importers\Customers\DocumentsImporter;
use App\Services\DataLoader\Testing\Data\DocumentsData;

class CustomerDocumentsImporterData extends DocumentsData {
    public const CUSTOMER = 'a04716f3-95de-4046-ab13-7a575cf67f85';
    public const LIMIT    = 50;
    public const CHUNK    = 10;

    protected function generateData(string $path): bool {
        return $this->dumpClientResponses($path, function (): bool {
            $this->app->make(DocumentsImporter::class)
                ->setCustomerId(static::CUSTOMER)
                ->setChunkSize(static::CHUNK)
                ->setLimit(static::LIMIT)
                ->start();

            return true;
        });
    }
}
