<?php declare(strict_types = 1);

namespace App\Services\Recalculator;

use App\Services\Recalculator\Commands\AssetsRecalculate;
use App\Services\Recalculator\Commands\CustomersRecalculate;
use App\Services\Recalculator\Commands\DocumentsRecalculate;
use App\Services\Recalculator\Commands\LocationsRecalculate;
use App\Services\Recalculator\Commands\ResellersRecalculate;
use App\Services\Recalculator\Listeners\DataImportedListener;
use App\Services\Recalculator\Listeners\DocumentDeleted;
use App\Services\Recalculator\Queue\Jobs\AssetsRecalculator;
use App\Services\Recalculator\Queue\Jobs\CustomersRecalculator;
use App\Services\Recalculator\Queue\Jobs\DocumentsRecalculator;
use App\Services\Recalculator\Queue\Jobs\LocationsRecalculator;
use App\Services\Recalculator\Queue\Jobs\ResellersRecalculator;
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
            $dispatcher->subscribe(DocumentDeleted::class);
        });
    }

    public function boot(): void {
        $this->bootCommands(
            ResellersRecalculate::class,
            CustomersRecalculate::class,
            LocationsRecalculate::class,
            AssetsRecalculate::class,
            DocumentsRecalculate::class,
        );
        $this->bootSchedule(
            ResellersRecalculator::class,
            CustomersRecalculator::class,
            LocationsRecalculator::class,
            AssetsRecalculator::class,
            DocumentsRecalculator::class,
        );
    }
}
