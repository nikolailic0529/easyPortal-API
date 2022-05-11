<?php declare(strict_types = 1);

namespace App\Services\Maintenance;

use App\Services\Maintenance\Commands\Start;
use App\Services\Maintenance\Commands\Stop;
use App\Services\Maintenance\Commands\VersionReset;
use App\Services\Maintenance\Commands\VersionUpdate;
use App\Services\Maintenance\Jobs\CompleteCronJob;
use App\Services\Maintenance\Jobs\NotifyCronJob;
use App\Services\Maintenance\Jobs\StartCronJob;
use App\Services\Maintenance\Jobs\TelescopeCleaner;
use Illuminate\Support\ServiceProvider;
use LastDragon_ru\LaraASP\Core\Concerns\ProviderWithCommands;
use LastDragon_ru\LaraASP\Queue\Concerns\ProviderWithSchedule;

class Provider extends ServiceProvider {
    use ProviderWithCommands;
    use ProviderWithSchedule;

    public function boot(): void {
        $this->bootCommands(
            Start::class,
            Stop::class,
            VersionReset::class,
            VersionUpdate::class,
        );
        $this->bootSchedule(
            TelescopeCleaner::class,
            StartCronJob::class,
            NotifyCronJob::class,
            CompleteCronJob::class,
        );
    }
}
