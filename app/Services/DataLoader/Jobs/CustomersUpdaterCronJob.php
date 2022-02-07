<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use Config\Constants;

/**
 * Search for outdated customers and update it.
 */
class CustomersUpdaterCronJob extends CustomersImporterCronJob {
    public function displayName(): string {
        return 'ep-data-loader-customers-updater';
    }

    /**
     * @return array<mixed>
     */
    public function getQueueConfig(): array {
        return [
                'settings' => [
                    'chunk'  => Constants::EP_DATA_LOADER_CUSTOMERS_UPDATER_CHUNK,
                    'expire' => Constants::EP_DATA_LOADER_CUSTOMERS_UPDATER_EXPIRE,
                ],
            ] + parent::getQueueConfig();
    }
}
