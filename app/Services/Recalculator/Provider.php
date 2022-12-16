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
use App\Utils\Providers\EventsProvider;
use App\Utils\Providers\ServiceServiceProvider;
use LastDragon_ru\LaraASP\Queue\Concerns\ProviderWithSchedule;

class Provider extends ServiceServiceProvider {
    use ProviderWithSchedule;

    /**
     * @var array<class-string<EventsProvider>>
     */
    protected array $listeners = [
        DataImportedListener::class,
        DocumentDeleted::class,
    ];

    public function register(): void {
        parent::register();

        $this->app->singleton(Recalculator::class);
    }

    public function boot(): void {
        $this->commands(
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
