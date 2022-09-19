<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Queue\Jobs;

use App\Services\DataLoader\Importer\Importers\Distributors\Importer;
use App\Services\DataLoader\Queue\Jobs\Importer as ImporterJob;
use App\Utils\Processor\Contracts\Processor;
use Config\Constants;
use Illuminate\Contracts\Container\Container;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;

/**
 * Imports distributors.
 *
 * @extends ImporterJob<Importer>
 */
class DistributorsImporter extends ImporterJob {
    public function displayName(): string {
        return 'ep-data-loader-distributors-importer';
    }

    /**
     * @return array<string, mixed>
     */
    public function getQueueConfig(): array {
        return [
                'settings' => [
                    'chunk'  => Constants::EP_DATA_LOADER_DISTRIBUTORS_IMPORTER_CHUNK,
                    'expire' => null,
                ],
            ] + parent::getQueueConfig();
    }

    protected function makeProcessor(Container $container, QueueableConfig $config): Processor {
        return $container->make(Importer::class);
    }
}
