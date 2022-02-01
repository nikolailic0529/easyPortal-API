<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Services\DataLoader\Importer\Importers\CustomersImporter;
use App\Services\DataLoader\Service;
use Config\Constants;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;

/**
 * Imports customers.
 */
class CustomersImporterCronJob extends ImporterCronJob {
    public function displayName(): string {
        return 'ep-data-loader-customers-importer';
    }

    /**
     * @return array<mixed>
     */
    public function getQueueConfig(): array {
        return [
                'settings' => [
                    'chunk'  => Constants::EP_DATA_LOADER_CUSTOMERS_IMPORTER_CHUNK,
                    'update' => Constants::EP_DATA_LOADER_CUSTOMERS_IMPORTER_UPDATE,
                    'expire' => null,
                ],
            ] + parent::getQueueConfig();
    }

    public function __invoke(
        Service $service,
        CustomersImporter $importer,
        QueueableConfigurator $configurator,
    ): void {
        $this->process($service, $importer, $configurator);
    }
}
