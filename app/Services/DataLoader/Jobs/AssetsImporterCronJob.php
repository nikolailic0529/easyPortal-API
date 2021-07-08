<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Services\DataLoader\Importers\AssetsImporter;
use Config\Constants;
use Illuminate\Contracts\Cache\Repository;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;

/**
 * Imports assets.
 */
class AssetsImporterCronJob extends ImporterCronJob {
    public function displayName(): string {
        return 'ep-data-loader-assets-importer';
    }

    /**
     * @return array<mixed>
     */
    public function getQueueConfig(): array {
        return [
                'settings' => [
                    'chunk'  => Constants::EP_DATA_LOADER_ASSETS_IMPORTER_CHUNK,
                    'update' => Constants::EP_DATA_LOADER_ASSETS_IMPORTER_UPDATE,
                    'expire' => null,
                ],
            ] + parent::getQueueConfig();
    }

    public function __invoke(
        Repository $cache,
        AssetsImporter $importer,
        QueueableConfigurator $configurator,
    ): void {
        $this->process($cache, $importer, $configurator);
    }
}
