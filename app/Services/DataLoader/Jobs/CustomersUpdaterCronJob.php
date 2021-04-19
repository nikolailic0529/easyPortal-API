<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Jobs\NamedJob;
use App\Models\Customer;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;
use LastDragon_ru\LaraASP\Queue\Queueables\CronJob;

/**
 * Search for outdated customers and update it.
 */
class CustomersUpdaterCronJob extends CronJob implements ShouldBeUnique, NamedJob {
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

    public function handle(Container $container, QueueableConfigurator $configurator): void {
        $config   = $configurator->config($this);
        $expire   = $config->setting('expire');
        $expire   = Date::now()->sub($expire);
        $outdated = Customer::query()
            ->where('updated_at', '<', $expire)
            ->get();

        foreach ($outdated as $customer) {
            $container
                ->make(CustomerUpdate::class)
                ->initialize($customer->getKey())
                ->dispatch();

            $customer->updated_at = Date::now();
            $customer->save();
        }
    }
}
