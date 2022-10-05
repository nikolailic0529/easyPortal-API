<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Queue\Jobs;

use App\Services\DataLoader\Processors\Synchronizer\Synchronizers\AssetsSynchronizer as Synchronizer;
use App\Services\DataLoader\Queue\Jobs\Synchronizer as SynchronizerJob;
use App\Utils\Processor\Contracts\Processor;
use Config\Constants;
use Illuminate\Contracts\Container\Container;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;

/**
 * @extends SynchronizerJob<Synchronizer>
 */
class AssetsSynchronizer extends SynchronizerJob {
    public function displayName(): string {
        return 'ep-data-loader-assets-synchronizer';
    }

    /**
     * @return array<string, mixed>
     */
    public function getQueueConfig(): array {
        return [
                'settings' => [
                    'chunk'           => Constants::EP_DATA_LOADER_ASSETS_SYNCHRONIZER_CHUNK,
                    'expire'          => Constants::EP_DATA_LOADER_ASSETS_SYNCHRONIZER_EXPIRE,
                    'outdated'        => Constants::EP_DATA_LOADER_ASSETS_SYNCHRONIZER_OUTDATED,
                    'outdated_limit'  => Constants::EP_DATA_LOADER_ASSETS_SYNCHRONIZER_OUTDATED_LIMIT,
                    'outdated_expire' => Constants::EP_DATA_LOADER_ASSETS_SYNCHRONIZER_OUTDATED_EXPIRE,
                ],
            ] + parent::getQueueConfig();
    }

    protected function makeProcessor(Container $container, QueueableConfig $config): Processor {
        return $container->make(Synchronizer::class);
    }
}
