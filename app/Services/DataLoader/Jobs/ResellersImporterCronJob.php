<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Services\DataLoader\Importers\ResellersImporter;
use Config\Constants;
use Illuminate\Contracts\Cache\Repository;
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
        Repository $cache,
        ResellersImporter $importer,
        QueueableConfigurator $configurator,
    ): void {
        $this->process($cache, $importer, $configurator);
    }
}
