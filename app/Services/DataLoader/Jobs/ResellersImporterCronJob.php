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
        ResellerFactory $resolver,
    ): void {
        $client   = $service->getClient();
        $prefetch = static function (array $resellers) use ($resolver): void {
            $resolver->prefetch($resellers);
        };

        foreach ($client->getResellers()->each($prefetch) as $reseller) {
            // If reseller exists we just skip it.
            if ($resolver->find($reseller)) {
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
