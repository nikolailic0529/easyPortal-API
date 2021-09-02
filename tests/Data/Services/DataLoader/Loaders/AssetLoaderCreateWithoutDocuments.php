<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Loaders;

use App\Services\DataLoader\Testing\Data\AssetsData;
use Illuminate\Console\Command;

use function array_merge;

class AssetLoaderCreateWithoutDocuments extends AssetsData {
    public const ASSET     = 'dd3fd852-c29a-4f34-ac20-21b6f7079777';
    public const DOCUMENTS = false;

    protected function generateData(string $path): bool {
        return $this->dumpClientResponses($path, function (): bool {
            $options = static::DOCUMENTS
                ? ['--documents' => true]
                : ['--no-documents' => true];
            $result  = $this->kernel->call('ep:data-loader-update-asset', array_merge($options, [
                'id'       => [static::ASSET],
                '--create' => true,
            ]));
            $success = $result === Command::SUCCESS;

            return $success;
        });
    }
}
