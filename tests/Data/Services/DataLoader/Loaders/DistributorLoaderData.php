<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Loaders;

use App\Services\DataLoader\Testing\Data\Data;
use Illuminate\Console\Command;

class DistributorLoaderData extends Data {
    public const DISTRIBUTOR = '1af1c44e-8112-4e72-9654-b11c705e9372';

    protected function generateData(string $path): bool {
        return $this->dumpClientResponses($path, function (): bool {
            $result  = $this->kernel->call('ep:data-loader-distributor-sync', [
                'id' => static::DISTRIBUTOR,
            ]);
            $success = $result === Command::SUCCESS;

            return $success;
        });
    }
}
