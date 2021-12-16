<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Importers;

use App\Services\DataLoader\Testing\Data\Data;
use Illuminate\Console\Command;

class ResellersImporterData extends Data {
    public const LIMIT = 50;
    public const CHUNK = 10;

    protected function generateData(string $path): bool {
        return $this->dumpClientResponses($path, function (): bool {
            $result  = $this->kernel->call('ep:data-loader-import-resellers', [
                '--limit' => static::LIMIT,
                '--chunk' => static::CHUNK,
            ]);
            $success = $result === Command::SUCCESS;

            return $success;
        });
    }
}
