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
use App\Services\DataLoader\Queue\Jobs\AssetsImporter;
use App\Services\DataLoader\Queue\Jobs\AssetsUpdater;
use App\Services\DataLoader\Queue\Jobs\CustomersImporter;
use App\Services\DataLoader\Queue\Jobs\CustomersUpdater;
use App\Services\DataLoader\Queue\Jobs\DistributorsImporter;
use App\Services\DataLoader\Queue\Jobs\DistributorsUpdater;
use App\Services\DataLoader\Queue\Jobs\DocumentsImporter;
use App\Services\DataLoader\Queue\Jobs\DocumentsUpdater;
use App\Services\DataLoader\Queue\Jobs\ResellersImporter;
use App\Services\DataLoader\Queue\Jobs\ResellersUpdater;
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
            DistributorsImporter::class,
            DistributorsUpdater::class,
            ResellersImporter::class,
            ResellersUpdater::class,
            CustomersImporter::class,
            CustomersUpdater::class,
            DocumentsImporter::class,
            DocumentsUpdater::class,
            AssetsImporter::class,
            AssetsUpdater::class,
        );
    }
}
