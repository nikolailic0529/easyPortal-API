<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Services\DataLoader\Importer\Importers\ResellersImporter;
use App\Utils\Processor\Processor;
use Config\Constants;
use Illuminate\Contracts\Container\Container;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;

/**
 * Imports resellers.
 *
 * @extends ImporterCronJob<ResellersImporter>
 */
class ResellersImporterCronJob extends ImporterCronJob {
    public function displayName(): string {
        return 'ep-data-loader-resellers-importer';
    }

    /**
     * @return array<string, mixed>
     */
    public function getQueueConfig(): array {
        return [
                'settings' => [
                    'chunk'  => Constants::EP_DATA_LOADER_RESELLERS_IMPORTER_CHUNK,
                    'expire' => null,
                ],
            ] + parent::getQueueConfig();
    }

    protected function makeProcessor(Container $container, QueueableConfig $config): Processor {
        return $container->make(ResellersImporter::class);
    }
}
