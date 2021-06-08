<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Jobs\NamedJob;
use App\Services\DataLoader\DataLoaderService;
use App\Services\DataLoader\Factories\ResellerFactory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use LastDragon_ru\LaraASP\Queue\Queueables\CronJob;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Imports reseller list.
 */
class ResellersImporterCronJob extends CronJob implements ShouldBeUnique, NamedJob {
    public function displayName(): string {
        return 'ep-data-loader-resellers-importer';
    }

    public function handle(
        Container $container,
        LoggerInterface $logger,
        DataLoaderService $service,
        ResellerFactory $factory,
    ): void {
        $client   = $service->getClient();
        $prefetch = static function (array $resellers) use ($factory): void {
            $factory->prefetch($resellers);
        };

        foreach ($client->getResellers()->each($prefetch) as $reseller) {
            // If reseller exists we just skip it.
            if ($factory->find($reseller)) {
                continue;
            }

            // If not - we create it and dispatch the job to update assets/documents
            try {
                $container
                    ->make(ResellerUpdate::class)
                    ->initialize($factory->create($reseller)->getKey())
                    ->dispatch();
            } catch (Throwable $exception) {
                $logger->warning(__METHOD__, [
                    'reseller'  => $reseller,
                    'exception' => $exception,
                ]);
            }
        }
    }
}
