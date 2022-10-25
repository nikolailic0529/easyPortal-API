<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Loaders;

use App\Services\DataLoader\Testing\Data\AssetsData;
use App\Services\DataLoader\Testing\Data\Context;
use Illuminate\Console\Command;

class DocumentLoaderData extends AssetsData {
    public const DOCUMENT = '00122a07-53e5-4c70-ba6b-bf51fcdd6695';

    protected function generateData(string $path, Context $context): bool {
        $result  = $this->kernel->call('ep:data-loader-document-sync', [
            'id' => static::DOCUMENT,
        ]);
        $success = $result === Command::SUCCESS;

        return $success;
    }
}
