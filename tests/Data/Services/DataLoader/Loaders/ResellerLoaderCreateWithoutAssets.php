<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Loaders;

use App\Services\DataLoader\Testing\Data\AssetsData;
use Illuminate\Console\Command;

use function array_merge;

class ResellerLoaderCreateWithoutAssets extends AssetsData {
    public const RESELLER = '6bbb0d14-6854-4dbb-9a2c-a1292ccf2e9e';
    public const ASSETS   = false;

    protected function generateData(string $path): bool {
        return $this->dumpClientResponses($path, function (): bool {
            $options = static::ASSETS
                ? ['--assets' => true]
                : ['--no-assets' => true];
            $result  = $this->kernel->call('ep:data-loader-update-reseller', array_merge($options, [
                'id'       => [static::RESELLER],
                '--create' => true,
            ]));
            $success = $result === Command::SUCCESS;

            return $success;
        });
    }
}
