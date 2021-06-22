<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Services\DataLoader\Importers\CustomersImporter;
use Config\Constants;
use Illuminate\Contracts\Cache\Repository;
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

    public function handle(
        Repository $cache,
        CustomersImporter $importer,
        QueueableConfigurator $configurator,
    ): void {
        $this->process($cache, $importer, $configurator);
    }
}
