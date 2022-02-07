<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use Config\Constants;

/**
 * Search for outdated resellers and update it.
 */
class ResellersUpdaterCronJob extends ResellersImporterCronJob {
    public function displayName(): string {
        return 'ep-data-loader-resellers-updater';
    }

    /**
     * @return array<mixed>
     */
    public function getQueueConfig(): array {
        return [
                'settings' => [
                    'chunk'  => Constants::EP_DATA_LOADER_RESELLERS_UPDATER_CHUNK,
                    'expire' => Constants::EP_DATA_LOADER_RESELLERS_UPDATER_EXPIRE,
                ],
            ] + parent::getQueueConfig();
    }
}
