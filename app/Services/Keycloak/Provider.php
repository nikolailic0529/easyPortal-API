<?php declare(strict_types = 1);

namespace App\Services\Keycloak;

use App\Services\Keycloak\Commands\PermissionsSync;
use App\Services\Keycloak\Commands\UsersSync;
use App\Services\Keycloak\Jobs\Cron\PermissionsSynchronizer;
use App\Services\Keycloak\Jobs\Cron\UsersSynchronizer;
use Illuminate\Support\ServiceProvider;
use LastDragon_ru\LaraASP\Core\Concerns\ProviderWithCommands;
use LastDragon_ru\LaraASP\Queue\Concerns\ProviderWithSchedule;

class Provider extends ServiceProvider {
    use ProviderWithCommands;
    use ProviderWithSchedule;

    public function boot(): void {
        $this->bootCommands(
            PermissionsSync::class,
            UsersSync::class,
        );
        $this->bootSchedule(
            PermissionsSynchronizer::class,
            UsersSynchronizer::class,
        );
    }
}
