<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Services\DataLoader\Importers\DistributorsImporter;
use Config\Constants;
use Illuminate\Contracts\Cache\Repository;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;

/**
 * Imports distributors.
 */
class DistributorsImporterCronJob extends ImporterCronJob {
    public function displayName(): string {
        return 'ep-data-loader-distributors-importer';
    }

    /**
     * @return array<mixed>
     */
    public function getQueueConfig(): array {
        return [
                'settings' => [
                    'chunk'  => Constants::EP_DATA_LOADER_DISTRIBUTORS_IMPORTER_CHUNK,
                    'update' => Constants::EP_DATA_LOADER_DISTRIBUTORS_IMPORTER_UPDATE,
                    'expire' => null,
                ],
            ] + parent::getQueueConfig();
    }

    public function handle(
        Repository $cache,
        DistributorsImporter $importer,
        QueueableConfigurator $configurator,
    ): void {
        $this->process($cache, $importer, $configurator);
    }
}
