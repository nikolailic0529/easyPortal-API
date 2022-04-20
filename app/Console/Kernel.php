<?php declare(strict_types = 1);

namespace App\Console;

use App\Console\Commands\TestCommand;
use App\Dev\IdeHelper\ModelsCommand;
use App\Services\DataLoader\Commands\AssetsAnalyze;
use App\Services\DataLoader\Commands\AssetsCount;
use App\Services\DataLoader\Commands\AssetsImport;
use App\Services\DataLoader\Commands\CustomersImport;
use App\Services\DataLoader\Commands\DistributorsImport;
use App\Services\DataLoader\Commands\DocumentsImport;
use App\Services\DataLoader\Commands\OemsImport;
use App\Services\DataLoader\Commands\ResellersImport;
use App\Services\DataLoader\Commands\UpdateAsset;
use App\Services\DataLoader\Commands\UpdateCustomer;
use App\Services\DataLoader\Commands\UpdateDistributor;
use App\Services\DataLoader\Commands\UpdateDocument;
use App\Services\DataLoader\Commands\UpdateReseller;
use App\Services\DataLoader\Jobs\AssetsImporterCronJob;
use App\Services\DataLoader\Jobs\AssetsUpdaterCronJob;
use App\Services\DataLoader\Jobs\CustomersImporterCronJob;
use App\Services\DataLoader\Jobs\CustomersUpdaterCronJob;
use App\Services\DataLoader\Jobs\DistributorsImporterCronJob;
use App\Services\DataLoader\Jobs\DistributorsUpdaterCronJob;
use App\Services\DataLoader\Jobs\DocumentsImporterCronJob;
use App\Services\DataLoader\Jobs\DocumentsUpdaterCronJob;
use App\Services\DataLoader\Jobs\ResellersImporterCronJob;
use App\Services\DataLoader\Jobs\ResellersUpdaterCronJob;
use App\Services\Queue\Jobs\SnapshotCronJob as QueueSnapshotCronJob;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use LastDragon_ru\LaraASP\Queue\Concerns\ConsoleKernelWithSchedule;
use LastDragon_ru\LaraASP\Queue\Contracts\Cronable;

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
        DistributorsImport::class,
        ResellersImport::class,
        CustomersImport::class,
        DocumentsImport::class,
        AssetsImport::class,
        OemsImport::class,
        UpdateDistributor::class,
        UpdateReseller::class,
        UpdateCustomer::class,
        UpdateDocument::class,
        UpdateAsset::class,
        AssetsCount::class,
        AssetsAnalyze::class,

        // Dev
        ModelsCommand::class,
        TestCommand::class,
    ];

    /**
     * The application's command schedule.
     *
     * @var array<class-string<Cronable>>
     */
    protected array $schedule = [
        QueueSnapshotCronJob::class,
        DistributorsImporterCronJob::class,
        DistributorsUpdaterCronJob::class,
        ResellersImporterCronJob::class,
        ResellersUpdaterCronJob::class,
        CustomersImporterCronJob::class,
        CustomersUpdaterCronJob::class,
        DocumentsImporterCronJob::class,
        DocumentsUpdaterCronJob::class,
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
