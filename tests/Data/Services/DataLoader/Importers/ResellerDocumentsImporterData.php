<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Importers;

use App\Services\DataLoader\Importer\Importers\ResellerDocumentsImporter;
use App\Services\DataLoader\Testing\Data\AssetsData;
use App\Services\DataLoader\Testing\Data\ClientDumpContext;

class ResellerDocumentsImporterData extends AssetsData {
    public const RESELLER = '1391a181-977e-489c-85ca-b2f9b450a765';
    public const LIMIT    = 50;
    public const CHUNK    = 10;

    protected function generateData(string $path): bool {
        return $this->dumpClientResponses($path, function (): bool {
            $this->app->make(ResellerDocumentsImporter::class)
                ->setResellerId(static::RESELLER)
                ->setChunkSize(static::CHUNK)
                ->setLimit(static::LIMIT)
                ->start();

            return true;
        });
    }

    /**
     * @inheritDoc
     */
    protected function generateContext(string $path): array {
        return $this->app->make(ClientDumpContext::class)->get($path, [
            ClientDumpContext::DISTRIBUTORS,
            ClientDumpContext::RESELLERS,
            ClientDumpContext::CUSTOMERS,
            ClientDumpContext::ASSETS,
            ClientDumpContext::TYPES,
            ClientDumpContext::OEMS,
        ]);
    }
}
