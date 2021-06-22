<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use Config\Constants;

/**
 * Update assets.
 */
class AssetsUpdaterCronJob extends AssetsImporterCronJob {
    public function displayName(): string {
        return 'ep-data-loader-assets-updater';
    }

    /**
     * @return array<mixed>
     */
    public function getQueueConfig(): array {
        return [
                'settings' => [
                    'chunk'  => Constants::EP_DATA_LOADER_ASSETS_UPDATER_CHUNK,
                    'update' => true,
                    'expire' => Constants::EP_DATA_LOADER_ASSETS_UPDATER_EXPIRE,
                ],
            ] + parent::getQueueConfig();
    }
}
