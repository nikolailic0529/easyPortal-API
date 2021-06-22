<?php declare(strict_types = 1);

namespace App\Console;

use App\Services\DataLoader\Commands\AnalyzeAssets;
use App\Services\DataLoader\Commands\ImportAssets;
use App\Services\DataLoader\Commands\ImportDistributors;
use App\Services\DataLoader\Commands\UpdateAsset;
use App\Services\DataLoader\Commands\UpdateCustomer;
use App\Services\DataLoader\Commands\UpdateDistributor;
use App\Services\DataLoader\Commands\UpdateReseller;
use App\Services\DataLoader\Jobs\AssetsImporterCronJob;
use App\Services\DataLoader\Jobs\AssetsUpdaterCronJob;
use App\Services\DataLoader\Jobs\CustomersImporterCronJob;
use App\Services\DataLoader\Jobs\CustomersUpdaterCronJob;
use App\Services\DataLoader\Jobs\DistributorsImporterCronJob;
use App\Services\DataLoader\Jobs\DistributorsUpdaterCronJob;
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
        ImportDistributors::class,
        ImportAssets::class,
        UpdateDistributor::class,
        UpdateReseller::class,
        UpdateCustomer::class,
        UpdateAsset::class,
        AnalyzeAssets::class,
    ];

    /**
     * The application's command schedule.
     *
     * @var array<class-string<\LastDragon_ru\LaraASP\Queue\Contracts\Cronable>>
     */
    protected array $schedule = [
        DistributorsImporterCronJob::class,
        DistributorsUpdaterCronJob::class,
        ResellersImporterCronJob::class,
        ResellersUpdaterCronJob::class,
        CustomersImporterCronJob::class,
        CustomersUpdaterCronJob::class,
        AssetsImporterCronJob::class,
        AssetsUpdaterCronJob::class,
    ];

    /**
     * Register the commands for the application.
     */
    protected function commands(): void {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
