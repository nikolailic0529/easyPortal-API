<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Concerns\GlobalScopes\GlobalScopes;
use App\Models\Model;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Client\QueryIterator;
use App\Services\DataLoader\Schema\Company;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;

/**
 * Search for outdated customers and update it.
 */
class CustomersUpdaterCronJob extends CustomersImporterCronJob {
    use GlobalScopes;

    public function displayName(): string {
        return 'ep-data-loader-customers-updater';
    }

    /**
     * @return array<mixed>
     */
    public function getQueueConfig(): array {
        return [
                'settings' => [
                    'expire' => '24 hours', // Expiration interval
                ],
            ] + parent::getQueueConfig();
    }

    protected function getCompanies(Client $client, QueueableConfig $config): QueryIterator {
        $expire   = $config->setting('expire');
        $expire   = Date::now()->sub($expire);
        $outdated = $client->getCustomers($expire);

        return $outdated;
    }

    protected function updateExistingCompany(Container $container, Company $company, Model $model): void {
        parent::updateCreatedCompany($container, $company, $model);
    }
}
