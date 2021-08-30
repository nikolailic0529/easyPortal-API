<?php declare(strict_types = 1);

namespace Tests\Data\DataLoader;

use App\Services\DataLoader\Testing\Data\Data;
use Illuminate\Console\Command;

class AssetsImporterData extends Data {
    public const LIMIT = 50;
    public const CHUNK = 10;

    public function generate(string $path): bool {
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
