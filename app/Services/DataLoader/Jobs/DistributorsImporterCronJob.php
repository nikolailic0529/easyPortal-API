<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Model;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Client\OffsetBasedIterator;
use App\Services\DataLoader\Factories\DistributorFactory;
use App\Services\DataLoader\Factories\ResellerFactory;
use App\Services\DataLoader\Factory;
use App\Services\DataLoader\Schema\Company;
use Illuminate\Contracts\Container\Container;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;

/**
 * Imports distributors.
 */
class DistributorsImporterCronJob extends CompanyUpdaterCronJob {
    public function displayName(): string {
        return 'ep-data-loader-distributors-importer';
    }

    protected function getFactory(Container $container): Factory {
        return $container->make(DistributorFactory::class);
    }

    protected function getCompanies(Client $client, QueueableConfig $config): OffsetBasedIterator {
        return $client->getDistributor();
    }

    protected function updateCreatedCompany(Container $container, Company $company, Model $model): void {
        $container
            ->make(DistributorUpdate::class)
            ->initialize($model->getKey())
            ->dispatch();
    }

    protected function updateExistingCompany(Container $container, Company $company, Model $model): void {
        // no action
    }
}
