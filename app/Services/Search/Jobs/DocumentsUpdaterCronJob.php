<?php declare(strict_types = 1);

namespace App\Services\Search\Jobs;

use App\Models\Document;
use App\Services\Search\Processor\Processor;
use App\Services\Search\Service;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;

/**
 * Updates search index for Documents (Contracts/Quotes).
 */
class DocumentsUpdaterCronJob extends UpdateIndexCronJob {
    public function displayName(): string {
        return 'ep-search-documents-updater';
    }

    public function __invoke(
        QueueableConfigurator $configurator,
        Service $service,
        Processor $updater,
    ): void {
        $this->process($configurator, $service, $updater, Document::class);
    }
}
