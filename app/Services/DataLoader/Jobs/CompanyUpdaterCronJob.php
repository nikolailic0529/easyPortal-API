<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Jobs\NamedJob;
use App\Models\Concerns\GlobalScopes\GlobalScopes;
use App\Models\Model;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Client\OffsetBasedIterator;
use App\Services\DataLoader\DataLoaderService;
use App\Services\DataLoader\Factory;
use App\Services\DataLoader\FactoryPrefetchable;
use App\Services\DataLoader\Schema\Company;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;
use LastDragon_ru\LaraASP\Queue\Queueables\CronJob;
use Psr\Log\LoggerInterface;
use Throwable;

use function sprintf;

abstract class CompanyUpdaterCronJob extends CronJob implements ShouldBeUnique, NamedJob {
    use GlobalScopes;

    public function handle(
        Container $container,
        LoggerInterface $logger,
        DataLoaderService $service,
        QueueableConfigurator $configurator,
    ): void {
        $this->callWithoutGlobalScopes(
            [OwnedByOrganizationScope::class],
            function () use ($container, $logger, $service, $configurator): void {
                $this->process($container, $logger, $service, $configurator);
            },
        );
    }

    protected function process(
        Container $container,
        LoggerInterface $logger,
        DataLoaderService $service,
        QueueableConfigurator $configurator,
    ): void {
        $client    = $service->getClient();
        $config    = $configurator->config($this);
        $factory   = $this->getFactory($container);
        $companies = $this->getCompanies($client, $config)->beforeChunk(
            static function (array $companies) use ($factory): void {
                if ($factory instanceof FactoryPrefetchable) {
                    $factory->prefetch($companies, true);
                }
            },
        );

        foreach ($companies as $company) {
            try {
                $model = $factory->find($company);

                if ($model) {
                    $this->updateExistingCompany($container, $company, $model);
                } else {
                    $this->updateCreatedCompany($container, $company, $factory->create($company));
                }
            } catch (Throwable $exception) {
                $logger->warning(sprintf('%s failed.', $this::class), [
                    'company'   => $company,
                    'exception' => $exception,
                ]);
            }
        }
    }

    abstract protected function getFactory(Container $container): Factory;

    /**
     * @return \App\Services\DataLoader\Client\OffsetBasedIterator<\App\Services\DataLoader\Schema\Company>
     */
    abstract protected function getCompanies(Client $client, QueueableConfig $config): OffsetBasedIterator;

    abstract protected function updateCreatedCompany(Container $container, Company $company, Model $model): void;

    abstract protected function updateExistingCompany(Container $container, Company $company, Model $model): void;
}
