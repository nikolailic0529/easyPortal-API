<?php declare(strict_types = 1);

namespace App\Services\Recalculator;

use App\Services\Recalculator\Commands\CustomersRecalculate;
use App\Services\Recalculator\Commands\LocationsRecalculate;
use App\Services\Recalculator\Commands\ResellersRecalculate;
use App\Services\Recalculator\Jobs\Cron\CustomersRecalculator;
use App\Services\Recalculator\Jobs\Cron\LocationsRecalculator;
use App\Services\Recalculator\Jobs\Cron\ResellersRecalculator;
use App\Services\Recalculator\Listeners\DataImportedListener;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use LastDragon_ru\LaraASP\Core\Concerns\ProviderWithCommands;
use LastDragon_ru\LaraASP\Queue\Concerns\ProviderWithSchedule;

class Provider extends ServiceProvider {
    use ProviderWithCommands;
    use ProviderWithSchedule;

    public function register(): void {
        parent::register();

        $this->registerListeners();
    }

    protected function registerListeners(): void {
        $this->booting(static function (Dispatcher $dispatcher): void {
            $dispatcher->subscribe(DataImportedListener::class);
        });
    }

    public function boot(): void {
        $this->bootCommands(
            ResellersRecalculate::class,
            CustomersRecalculate::class,
            LocationsRecalculate::class,
        );
        $this->bootSchedule(
            ResellersRecalculator::class,
            CustomersRecalculator::class,
            LocationsRecalculator::class,
        );
    }
}
