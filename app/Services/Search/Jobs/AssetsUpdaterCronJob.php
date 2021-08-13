<?php declare(strict_types = 1);

namespace App\Services\Search\Jobs;

use App\Models\Asset;
use App\Services\Search\Service;
use App\Services\Search\Updater;
use Config\Constants;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;

class AssetsUpdaterCronJob extends UpdateIndexCronJob {
    public function displayName(): string {
        return 'ep-search-assets-updater';
    }

    /**
     * @return array<mixed>
     */
    public function getQueueConfig(): array {
        return [
                'settings' => [
                    'chunk' => Constants::EP_SEARCH_ASSETS_UPDATER_CHUNK,
                ],
            ] + parent::getQueueConfig();
    }

    public function __invoke(
        QueueableConfigurator $configurator,
        Service $service,
        Updater $updater,
    ): void {
        $this->process($configurator, $service, $updater, Asset::class);
    }
}
