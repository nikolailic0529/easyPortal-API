<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use Config\Constants;

/**
 * Search for outdated documents and update it.
 */
class DocumentsUpdaterCronJob extends DocumentsImporterCronJob {
    public function displayName(): string {
        return 'ep-data-loader-documents-updater';
    }

    /**
     * @return array<string, mixed>
     */
    public function getQueueConfig(): array {
        return [
                'settings' => [
                    'chunk'  => Constants::EP_DATA_LOADER_DOCUMENTS_UPDATER_CHUNK,
                    'expire' => Constants::EP_DATA_LOADER_DOCUMENTS_UPDATER_EXPIRE,
                ],
            ] + parent::getQueueConfig();
    }
}
