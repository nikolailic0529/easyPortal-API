<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Reseller;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;
use LastDragon_ru\LaraASP\Queue\Queueables\CronJob;

/**
 * Search for outdated resellers and update it.
 */
class ResellersUpdaterCronJob extends CronJob implements ShouldBeUnique {
    /**
     * @return array<mixed>
     */
    public function getQueueConfig(): array {
        return [
                'settings' => [
                    'expire' => '24 hours', // Expiration interval, seconds
                ],
            ] + parent::getQueueConfig();
    }

    public function handle(Container $container, QueueableConfigurator $configurator): void {
        $config   = $configurator->config($this);
        $expire   = $config->setting('expire');
        $expire   = Date::now()->sub($expire);
        $outdated = Reseller::query()
            ->where('updated_at', '<', $expire)
            ->get();

        foreach ($outdated as $reseller) {
            $container
                ->make(ResellerUpdate::class)
                ->initialize($reseller->getKey())
                ->dispatch();

            $reseller->updated_at = Date::now();
            $reseller->save();
        }
    }
}
