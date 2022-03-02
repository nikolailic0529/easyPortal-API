<?php declare(strict_types = 1);

namespace App\Services\Search\Jobs;

use App\Models\Asset;
use App\Services\Search\Processor\Processor;
use App\Services\Search\Service;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;

/**
 * Updates search index for Assets.
 */
class AssetsUpdaterCronJob extends UpdateIndexCronJob {
    public function displayName(): string {
        return 'ep-search-assets-updater';
    }

    public function __invoke(
        QueueableConfigurator $configurator,
        Service $service,
        Processor $updater,
    ): void {
        $this->process($configurator, $service, $updater, Asset::class);
    }
}
