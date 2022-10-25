<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Loaders;

use App\Services\DataLoader\Testing\Data\AssetsData;
use App\Services\DataLoader\Testing\Data\Context;
use Illuminate\Console\Command;

class AssetLoaderData extends AssetsData {
    public const ASSET = '00000b7e-e7dc-49ee-8294-e4708b2435e4';

    protected function generateData(string $path, Context $context): bool {
        $result  = $this->kernel->call('ep:data-loader-asset-sync', [
            'id' => static::ASSET,
        ]);
        $success = $result === Command::SUCCESS;

        return $success;
    }
}
