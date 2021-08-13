<?php declare(strict_types = 1);

namespace App\Services\Search\Jobs;

use App\Models\Customer;
use App\Services\Search\Service;
use App\Services\Search\Updater;
use Config\Constants;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;

class CustomersUpdaterCronJob extends UpdateIndexCronJob {
    public function displayName(): string {
        return 'ep-search-customers-updater';
    }

    /**
     * @return array<mixed>
     */
    public function getQueueConfig(): array {
        return [
                'settings' => [
                    'chunk' => Constants::EP_SEARCH_CUSTOMERS_UPDATER_CHUNK,
                ],
            ] + parent::getQueueConfig();
    }

    public function __invoke(
        QueueableConfigurator $configurator,
        Service $service,
        Updater $updater,
    ): void {
        $this->process($configurator, $service, $updater, Customer::class);
    }
}
