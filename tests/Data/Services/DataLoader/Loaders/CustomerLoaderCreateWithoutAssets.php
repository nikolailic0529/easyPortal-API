<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Loaders;

use App\Services\DataLoader\Testing\Data\AssetsData;
use Illuminate\Console\Command;

use function array_merge;

class CustomerLoaderCreateWithoutAssets extends AssetsData {
    public const CUSTOMER = 'a0df13a5-c42c-4269-ae57-71085acb5319';
    public const ASSETS   = false;

    protected function generateData(string $path): bool {
        return $this->dumpClientResponses($path, function (): bool {
            $options = static::ASSETS
                ? ['--assets' => true]
                : ['--no-assets' => true];
            $result  = $this->kernel->call('ep:data-loader-update-customer', array_merge($options, [
                'id'       => [static::CUSTOMER],
                '--create' => true,
            ]));
            $success = $result === Command::SUCCESS;

            return $success;
        });
    }
}
