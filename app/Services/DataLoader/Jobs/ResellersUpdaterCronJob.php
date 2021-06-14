<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Model;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Client\OffsetBasedIterator;
use App\Services\DataLoader\Schema\Company;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;

/**
 * Search for outdated resellers and update it.
 */
class ResellersUpdaterCronJob extends ResellersImporterCronJob {
    public function displayName(): string {
        return 'ep-data-loader-resellers-updater';
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

    protected function getCompanies(Client $client, QueueableConfig $config): OffsetBasedIterator {
        $expire   = $config->setting('expire');
        $expire   = Date::now()->sub($expire);
        $outdated = $client->getResellers($expire);

        return $outdated;
    }

    protected function updateExistingCompany(Container $container, Company $company, Model $model): void {
        parent::updateCreatedCompany($container, $company, $model);
    }
}
