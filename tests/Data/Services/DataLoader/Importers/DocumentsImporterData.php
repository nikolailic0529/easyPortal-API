<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Importers;

use App\Services\DataLoader\Testing\Data\DocumentsData;
use Illuminate\Console\Command;

class DocumentsImporterData extends DocumentsData {
    public const OFFSET = '188d13aa-9c37-4c4d-8a9e-c4364b27783e';

    protected function generateData(string $path): bool {
        return $this->dumpClientResponses($path, function (): bool {
            $result  = $this->kernel->call('ep:data-loader-documents-import', [
                '--limit'  => static::LIMIT,
                '--chunk'  => static::CHUNK,
                '--offset' => static::OFFSET,
            ]);
            $success = $result === Command::SUCCESS;

            return $success;
        });
    }
}
