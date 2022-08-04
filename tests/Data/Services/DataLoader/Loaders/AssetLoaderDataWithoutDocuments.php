<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Loaders;

use App\Services\DataLoader\Testing\Data\AssetsData;
use Illuminate\Console\Command;

use function array_merge;

class AssetLoaderDataWithoutDocuments extends AssetsData {
    public const ASSET     = '00000b7e-e7dc-49ee-8294-e4708b2435e4';
    public const DOCUMENTS = false;

    protected function generateData(string $path): bool {
        return $this->dumpClientResponses($path, function (): bool {
            $options = static::DOCUMENTS
                ? ['--documents' => true]
                : ['--no-documents' => true];
            $result  = $this->kernel->call('ep:data-loader-asset-update', array_merge($options, [
                'id' => static::ASSET,
            ]));
            $success = $result === Command::SUCCESS;

            return $success;
        });
    }
}
