<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Importers;

use App\Services\DataLoader\Testing\Data\AssetsData;
use App\Services\DataLoader\Testing\Data\ClientDumpContext;
use Illuminate\Console\Command;

class DocumentsImporterData extends AssetsData {
    public const LIMIT  = 15;
    public const CHUNK  = 5;
    public const OFFSET = '0017faba-7a1a-44b2-9622-39b1b06879c4';

    protected function generateData(string $path): bool {
        return $this->dumpClientResponses($path, function (): bool {
            $result  = $this->kernel->call('ep:data-loader-import-documents', [
                '--limit'  => static::LIMIT,
                '--chunk'  => static::CHUNK,
                '--offset' => static::OFFSET,
            ]);
            $success = $result === Command::SUCCESS;

            return $success;
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
