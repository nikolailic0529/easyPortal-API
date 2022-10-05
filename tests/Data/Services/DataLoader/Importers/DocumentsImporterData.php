<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Importers;

use App\Services\DataLoader\Testing\Data\DocumentsData;
use Illuminate\Console\Command;

class DocumentsImporterData extends DocumentsData {
    protected function generateData(string $path): bool {
        return $this->dumpClientResponses($path, function (): bool {
            $result  = $this->kernel->call('ep:data-loader-documents-sync', [
                '--chunk' => static::CHUNK,
            ]);
            $success = $result === Command::SUCCESS;

            return $success;
        });
    }
}
