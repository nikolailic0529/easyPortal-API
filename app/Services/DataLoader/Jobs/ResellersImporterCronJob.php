<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Services\DataLoader\Importer\Importers\ResellersImporter;
use App\Services\DataLoader\Service;
use Config\Constants;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;

/**
 * Imports resellers.
 */
class ResellersImporterCronJob extends ImporterCronJob {
    public function displayName(): string {
        return 'ep-data-loader-resellers-importer';
    }

    /**
     * @return array<mixed>
     */
    public function getQueueConfig(): array {
        return [
                'settings' => [
                    'chunk'  => Constants::EP_DATA_LOADER_RESELLERS_IMPORTER_CHUNK,
                    'update' => Constants::EP_DATA_LOADER_RESELLERS_IMPORTER_UPDATE,
                    'expire' => null,
                ],
            ] + parent::getQueueConfig();
    }

    public function __invoke(
        Service $service,
        ResellersImporter $importer,
        QueueableConfigurator $configurator,
    ): void {
        $this->process($service, $importer, $configurator);
    }
}
