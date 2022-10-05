<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Queue\Jobs;

use App\Services\DataLoader\Processors\Synchronizer\Synchronizers\DistributorsSynchronizer;
use App\Utils\Processor\Contracts\Processor;
use Config\Constants;
use Illuminate\Contracts\Container\Container;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;

/**
 * @extends Importer<DistributorsSynchronizer>
 */
class DistributorsImporter extends Importer {
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
        return $container->make(DistributorsSynchronizer::class);
    }
}
