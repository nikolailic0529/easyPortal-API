<?php declare(strict_types = 1);

namespace Tests\Data\DataLoader;

use App\Services\DataLoader\Testing\Data\Data;
use Illuminate\Console\Command;

class DistributorsImporterData extends Data {
    public const LIMIT = 50;
    public const CHUNK = 10;

    public function generate(string $path): array|bool {
        return $this->dumpClientResponses($path, function (): bool {
            $result  = $this->kernel->call('ep:data-loader-import-distributors', [
                '--limit' => static::LIMIT,
                '--chunk' => static::CHUNK,
            ]);
            $success = $result === Command::SUCCESS;

            return $success;
        });
    }
}
