<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Importers;

use App\Services\DataLoader\Testing\Data\Context;
use App\Services\DataLoader\Testing\Data\Data;
use Illuminate\Console\Command;

class CustomersImporterData extends Data {
    protected function generateData(string $path, Context $context): bool {
        $result  = $this->kernel->call('ep:data-loader-customers-sync', [
            '--chunk' => static::CHUNK,
        ]);
        $success = $result === Command::SUCCESS;

        return $success;
    }

    /**
     * @inheritdoc
     */
    protected function getSupporterContext(): array {
        return [
            Context::RESELLERS,
        ];
    }
}
