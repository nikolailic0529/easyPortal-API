<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Loaders;

use App\Services\DataLoader\Testing\Data\Data;
use Illuminate\Console\Command;

class DistributorLoaderData extends Data {
    public const DISTRIBUTOR = '143c456a-e894-4710-a1c2-745b9582ca47';

    protected function generateData(string $path): bool {
        return $this->dumpClientResponses($path, function (): bool {
            $result  = $this->kernel->call('ep:data-loader-distributor-update', [
                'id' => [static::DISTRIBUTOR],
            ]);
            $success = $result === Command::SUCCESS;

            return $success;
        });
    }
}
