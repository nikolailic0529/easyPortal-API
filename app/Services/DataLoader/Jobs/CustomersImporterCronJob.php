<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Model;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Client\OffsetBasedIterator;
use App\Services\DataLoader\Factories\CustomerFactory;
use App\Services\DataLoader\Factory;
use App\Services\DataLoader\Schema\Company;
use Illuminate\Contracts\Container\Container;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;

/**
 * Imports customer list.
 */
class CustomersImporterCronJob extends CompanyUpdaterCronJob {
    public function displayName(): string {
        return 'ep-data-loader-customers-importer';
    }

    protected function getFactory(Container $container): Factory {
        return $container->make(CustomerFactory::class);
    }

    protected function getCompanies(Client $client, QueueableConfig $config): OffsetBasedIterator {
        return $client->getCustomers();
    }

    protected function updateCreatedCompany(Container $container, Company $company, Model $model): void {
        $container
            ->make(CustomerUpdate::class)
            ->initialize($model->getKey())
            ->dispatch();
    }

    protected function updateExistingCompany(Container $container, Company $company, Model $model): void {
        // no action
    }
}
