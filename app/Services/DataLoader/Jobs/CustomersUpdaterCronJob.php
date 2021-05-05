<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Jobs\NamedJob;
use App\Models\Concerns\GlobalScopes\GlobalScopes;
use App\Models\Customer;
use App\Services\Tenant\Eloquent\OwnedByTenantScope;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;
use LastDragon_ru\LaraASP\Queue\Queueables\CronJob;

/**
 * Search for outdated customers and update it.
 */
class CustomersUpdaterCronJob extends CronJob implements ShouldBeUnique, NamedJob {
    use GlobalScopes;

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
        $outdated = $this->callWithoutGlobalScopes([OwnedByTenantScope::class], static function () use ($expire) {
            return Customer::query()
                ->where('updated_at', '<', $expire)
                ->get();
        });

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
