<?php declare(strict_types = 1);

namespace App\Services\Maintenance;

use App\Services\Maintenance\Commands\VersionUpdate;
use Illuminate\Support\ServiceProvider;
use LastDragon_ru\LaraASP\Core\Concerns\ProviderWithCommands;

class Provider extends ServiceProvider {
    use ProviderWithCommands;

    public function boot(): void {
        $this->bootCommands(
            VersionUpdate::class,
        );
    }
}