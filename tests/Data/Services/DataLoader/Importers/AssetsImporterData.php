<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Importers;

use App\Services\DataLoader\Testing\Data\AssetsData;
use Illuminate\Console\Command;

class AssetsImporterData extends AssetsData {
    public const LIMIT = 50;
    public const CHUNK = 10;

    protected function generateData(string $path): bool {
        return $this->dumpClientResponses($path, function (): bool {
            $result  = $this->kernel->call('ep:data-loader-import-assets', [
                '--limit' => static::LIMIT,
                '--chunk' => static::CHUNK,
            ]);
            $success = $result === Command::SUCCESS;

            return $success;
        });
    }
}
