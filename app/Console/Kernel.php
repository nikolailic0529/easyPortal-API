<?php declare(strict_types = 1);

namespace App\Console;

use App\Services\DataLoader\Commands\AnalyzeAssets;
use App\Services\DataLoader\Commands\LoadAsset;
use App\Services\DataLoader\Commands\LoadCustomer;
use App\Services\DataLoader\Commands\LoadDistributor;
use App\Services\DataLoader\Commands\LoadReseller;
use App\Services\DataLoader\Jobs\CustomersImporterCronJob;
use App\Services\DataLoader\Jobs\CustomersUpdaterCronJob;
use App\Services\DataLoader\Jobs\ResellersImporterCronJob;
use App\Services\DataLoader\Jobs\ResellersUpdaterCronJob;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use LastDragon_ru\LaraASP\Queue\Concerns\ConsoleKernelWithSchedule;

use function base_path;

class Kernel extends ConsoleKernel {
    use ConsoleKernelWithSchedule;

    /**
     * The Artisan commands provided by your application.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $commands = [
        LoadDistributor::class,
        LoadReseller::class,
        LoadCustomer::class,
        LoadAsset::class,
        AnalyzeAssets::class,
    ];

    /**
     * The application's command schedule.
     *
     * @var array<class-string<\LastDragon_ru\LaraASP\Queue\Contracts\Cronable>>
     */
    protected array $schedule = [
        ResellersImporterCronJob::class,
        ResellersUpdaterCronJob::class,
        CustomersImporterCronJob::class,
        CustomersUpdaterCronJob::class,
    ];

    /**
     * Register the commands for the application.
     */
    protected function commands(): void {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
