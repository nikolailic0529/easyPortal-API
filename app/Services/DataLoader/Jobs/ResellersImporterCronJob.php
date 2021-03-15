<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Organization;
use App\Services\DataLoader\DataLoaderService;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use LastDragon_ru\LaraASP\Queue\Queueables\CronJob;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Imports reseller list.
 */
class ResellersImporterCronJob extends CronJob implements ShouldBeUnique {
    public function handle(Container $container, LoggerInterface $logger, DataLoaderService $service): void {
        $client = $service->getClient();

        foreach ($client->getResellers() as $reseller) {
            // If organization exists we just skip it.
            if (Organization::query()->whereKey($reseller->id)->exists()) {
                continue;
            }

            // If not - we dispatch a job to import it.
            try {
                $container
                    ->make(ResellerUpdate::class)
                    ->initialize($reseller->id)
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
