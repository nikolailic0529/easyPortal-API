<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use Config\Constants;

/**
 * Search for outdated distributors and update it.
 */
class DistributorsUpdaterCronJob extends DistributorsImporterCronJob {
    public function displayName(): string {
        return 'ep-data-loader-distributors-updater';
    }

    /**
     * @return array<mixed>
     */
    public function getQueueConfig(): array {
        return [
                'settings' => [
                    'chunk'  => Constants::EP_DATA_LOADER_DISTRIBUTORS_UPDATER_CHUNK,
                    'expire' => Constants::EP_DATA_LOADER_DISTRIBUTORS_UPDATER_EXPIRE,
                ],
            ] + parent::getQueueConfig();
    }
}
