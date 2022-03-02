<?php declare(strict_types = 1);

namespace App\Services\Search\Jobs;

use App\Models\Customer;
use App\Services\Search\Processor\Processor;
use App\Services\Search\Service;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;

/**
 * Updates search index for Customers.
 */
class CustomersUpdaterCronJob extends UpdateIndexCronJob {
    public function displayName(): string {
        return 'ep-search-customers-updater';
    }

    public function __invoke(
        QueueableConfigurator $configurator,
        Service $service,
        Processor $updater,
    ): void {
        $this->process($configurator, $service, $updater, Customer::class);
    }
}
