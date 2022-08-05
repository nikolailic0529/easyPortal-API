<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Loaders;

use App\Services\DataLoader\Testing\Data\AssetsData;
use Illuminate\Console\Command;

class DocumentLoaderData extends AssetsData {
    public const DOCUMENT = '00122a07-53e5-4c70-ba6b-bf51fcdd6695';

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
