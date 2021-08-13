<?php declare(strict_types = 1);

namespace App\Console;

use App\Dev\IdeHelper\ModelsCommand;
use App\Jobs\HorizonSnapshotCronJob;
use App\Services\DataLoader\Commands\AnalyzeAssets;
use App\Services\DataLoader\Commands\ImportAssets;
use App\Services\DataLoader\Commands\ImportCustomers;
use App\Services\DataLoader\Commands\ImportDistributors;
use App\Services\DataLoader\Commands\ImportOems;
use App\Services\DataLoader\Commands\ImportResellers;
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
use App\Services\KeyCloak\Commands\SyncPermissions;
use App\Services\KeyCloak\Jobs\SyncPermissionsCronJob;
use App\Services\Search\Jobs\AssetsUpdaterCronJob as SearchAssetsUpdaterCronJob;
use App\Services\Search\Jobs\CustomersUpdaterCronJob as SearchCustomersUpdaterCronJob;
use App\Services\Search\Jobs\DocumentsUpdaterCronJob as SearchDocumentsUpdaterCronJob;
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
        // App
        ImportDistributors::class,
        ImportResellers::class,
        ImportCustomers::class,
        ImportAssets::class,
        ImportOems::class,
        UpdateDistributor::class,
        UpdateReseller::class,
        UpdateCustomer::class,
        UpdateAsset::class,
        AnalyzeAssets::class,
        SyncPermissions::class,

        // Dev
        ModelsCommand::class,
    ];

    /**
     * The application's command schedule.
     *
     * @var array<class-string<\LastDragon_ru\LaraASP\Queue\Contracts\Cronable>>
     */
    protected array $schedule = [
        HorizonSnapshotCronJob::class,
        DistributorsImporterCronJob::class,
        DistributorsUpdaterCronJob::class,
        ResellersImporterCronJob::class,
        ResellersUpdaterCronJob::class,
        CustomersImporterCronJob::class,
        CustomersUpdaterCronJob::class,
        AssetsImporterCronJob::class,
        AssetsUpdaterCronJob::class,
        SyncPermissionsCronJob::class,
        SearchAssetsUpdaterCronJob::class,
        SearchCustomersUpdaterCronJob::class,
        SearchDocumentsUpdaterCronJob::class,
    ];

    /**
     * Register the commands for the application.
     */
    protected function commands(): void {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
