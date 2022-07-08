<?php declare(strict_types = 1);

namespace App\Services\Keycloak;

use App\Services\Keycloak\Auth\UserProvider;
use App\Services\Keycloak\Commands\PermissionsSync;
use App\Services\Keycloak\Commands\UsersSync;
use App\Services\Keycloak\Jobs\Cron\PermissionsSynchronizer;
use App\Services\Keycloak\Jobs\Cron\UsersSynchronizer;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use LastDragon_ru\LaraASP\Core\Concerns\ProviderWithCommands;
use LastDragon_ru\LaraASP\Queue\Concerns\ProviderWithSchedule;

class Provider extends ServiceProvider {
    use ProviderWithCommands;
    use ProviderWithSchedule;

    public function boot(): void {
        $this->bootKeycloak();
        $this->bootCommands(
            PermissionsSync::class,
            UsersSync::class,
        );
        $this->bootSchedule(
            PermissionsSynchronizer::class,
            UsersSynchronizer::class,
        );
    }

    protected function bootKeycloak(): void {
        $this->app->singleton(Keycloak::class);
        $this->app->make(AuthManager::class)->provider(
            UserProvider::class,
            static function (Application $app) {
                return $app->make(UserProvider::class);
            },
        );
    }
}
