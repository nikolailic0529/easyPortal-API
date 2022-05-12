<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Loaders;

use App\Services\DataLoader\Testing\Data\AssetsData;
use Illuminate\Console\Command;

class DocumentLoaderData extends AssetsData {
    public const DOCUMENT = 'c63823c9-ccae-493b-92ca-5cb2a696da69';

    protected function generateData(string $path): bool {
        return $this->dumpClientResponses($path, function (): bool {
            $result  = $this->kernel->call('ep:data-loader-document-update', [
                'id' => static::DOCUMENT,
            ]);
            $success = $result === Command::SUCCESS;

            return $success;
        });
    }
}
