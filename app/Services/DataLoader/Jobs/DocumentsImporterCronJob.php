<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Services\DataLoader\Importers\DocumentsImporter;
use App\Services\DataLoader\Service;
use Config\Constants;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;

/**
 * Imports documents.
 */
class DocumentsImporterCronJob extends ImporterCronJob {
    public function displayName(): string {
        return 'ep-data-loader-documents-importer';
    }

    /**
     * @return array<mixed>
     */
    public function getQueueConfig(): array {
        return [
                'settings' => [
                    'chunk'  => Constants::EP_DATA_LOADER_DOCUMENTS_IMPORTER_CHUNK,
                    'update' => Constants::EP_DATA_LOADER_DOCUMENTS_IMPORTER_UPDATE,
                    'expire' => null,
                ],
            ] + parent::getQueueConfig();
    }

    public function __invoke(
        Service $service,
        DocumentsImporter $importer,
        QueueableConfigurator $configurator,
    ): void {
        $this->process($service, $importer, $configurator);
    }
}
