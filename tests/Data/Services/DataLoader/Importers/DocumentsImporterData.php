<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Importers;

use App\Services\DataLoader\Testing\Data\Context;
use App\Services\DataLoader\Testing\Data\DocumentsData;
use Illuminate\Console\Command;
use LastDragon_ru\LaraASP\Testing\Utils\TestData;

class DocumentsImporterData extends DocumentsData {
    protected function generateData(TestData $root, Context $context): bool {
        $result  = $this->kernel->call('ep:data-loader-documents-sync', [
            '--chunk' => static::CHUNK,
        ]);
        $success = $result === Command::SUCCESS;

        return $success;
    }
}
