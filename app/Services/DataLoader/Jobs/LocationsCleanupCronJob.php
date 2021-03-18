<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Location;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Queue\Queueables\CronJob;
use Psr\Log\LoggerInterface;
use Throwable;

class LocationsCleanupCronJob extends CronJob {
    public function handle(LoggerInterface $logger): void {
        $expire    = Date::now()->sub('1 hour');
        $locations = Location::query()
            ->where('created_at', '<', $expire)
            ->whereNull('object_id')
            ->doesntHave('assets');

        foreach ($locations->iterator()->safe() as $location) {
            try {
                $location->delete();

                $logger->info(__METHOD__, [
                    'location' => $location,
                ]);
            } catch (Throwable $exception) {
                $logger->warning(__METHOD__, [
                    'location'  => $location,
                    'exception' => $exception,
                ]);
            }
        }
    }
}
