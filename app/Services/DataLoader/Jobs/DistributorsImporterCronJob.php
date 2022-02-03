<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Services\DataLoader\Importer\Importers\DistributorsImporter;
use App\Utils\Processor\Processor;
use Config\Constants;
use Illuminate\Contracts\Container\Container;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;

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

    protected function makeProcessor(Container $container, QueueableConfig $config): Processor {
        return $container->make(DistributorsImporter::class);
    }
}
