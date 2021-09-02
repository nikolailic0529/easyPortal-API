<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Loaders;

use App\Services\DataLoader\Testing\Data\Data;
use Illuminate\Console\Command;

class DistributorLoaderCreate extends Data {
    public const DISTRIBUTOR = '143c456a-e894-4710-a1c2-745b9582ca47';

    public function generate(string $path): bool|array {
        return $this->dumpClientResponses($path, function (): bool {
            $result  = $this->kernel->call('ep:data-loader-update-distributor', [
                'id'       => [static::DISTRIBUTOR],
                '--create' => true,
            ]);
            $success = $result === Command::SUCCESS;

            return $success;
        });
    }
}
