<?php declare(strict_types = 1);

namespace App\Services\Recalculator;

use App\Services\Recalculator\Commands\RecalculateCustomers;
use App\Services\Recalculator\Commands\RecalculateLocations;
use App\Services\Recalculator\Commands\RecalculateResellers;
use App\Services\Recalculator\Listeners\DataImportedListener;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use LastDragon_ru\LaraASP\Core\Concerns\ProviderWithCommands;

class Provider extends ServiceProvider {
    use ProviderWithCommands;

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
            RecalculateCustomers::class,
            RecalculateLocations::class,
            RecalculateResellers::class,
        );
    }
}
