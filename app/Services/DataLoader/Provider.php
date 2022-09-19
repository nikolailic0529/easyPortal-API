<?php declare(strict_types = 1);

namespace App\Services\DataLoader;

use App\Services\DataLoader\Commands\AssetsAnalyze;
use App\Services\DataLoader\Commands\AssetsSync;
use App\Services\DataLoader\Commands\AssetSync;
use App\Services\DataLoader\Commands\CustomersSync;
use App\Services\DataLoader\Commands\CustomerSync;
use App\Services\DataLoader\Commands\DistributorsSync;
use App\Services\DataLoader\Commands\DistributorSync;
use App\Services\DataLoader\Commands\DocumentsSync;
use App\Services\DataLoader\Commands\DocumentSync;
use App\Services\DataLoader\Commands\OemsImport;
use App\Services\DataLoader\Commands\ResellersSync;
use App\Services\DataLoader\Commands\ResellerSync;
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
use Illuminate\Support\ServiceProvider;
use LastDragon_ru\LaraASP\Core\Concerns\ProviderWithCommands;
use LastDragon_ru\LaraASP\Queue\Concerns\ProviderWithSchedule;

class Provider extends ServiceProvider {
    use ProviderWithCommands;
    use ProviderWithSchedule;

    public function boot(): void {
        $this->bootCommands(
            DistributorsSync::class,
            ResellersSync::class,
            CustomersSync::class,
            DocumentsSync::class,
            AssetsSync::class,
            OemsImport::class,
            DistributorSync::class,
            ResellerSync::class,
            CustomerSync::class,
            DocumentSync::class,
            AssetSync::class,
            AssetsAnalyze::class,
        );
        $this->bootSchedule(
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
        );
    }
}
