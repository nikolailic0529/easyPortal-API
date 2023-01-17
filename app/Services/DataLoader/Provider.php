<?php declare(strict_types = 1);

namespace App\Services\DataLoader;

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
use App\Services\DataLoader\Queue\Jobs\AssetsSynchronizer;
use App\Services\DataLoader\Queue\Jobs\CustomersImporter;
use App\Services\DataLoader\Queue\Jobs\CustomersSynchronizer;
use App\Services\DataLoader\Queue\Jobs\DistributorsImporter;
use App\Services\DataLoader\Queue\Jobs\DistributorsSynchronizer;
use App\Services\DataLoader\Queue\Jobs\DocumentsImporter;
use App\Services\DataLoader\Queue\Jobs\DocumentsSynchronizer;
use App\Services\DataLoader\Queue\Jobs\ResellersImporter;
use App\Services\DataLoader\Queue\Jobs\ResellersSynchronizer;
use Illuminate\Support\ServiceProvider;
use LastDragon_ru\LaraASP\Queue\Concerns\ProviderWithSchedule;

class Provider extends ServiceProvider {
    use ProviderWithSchedule;

    public function boot(): void {
        $this->commands(
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
        );
        $this->bootSchedule(
            DistributorsImporter::class,
            DistributorsSynchronizer::class,
            ResellersImporter::class,
            ResellersSynchronizer::class,
            CustomersImporter::class,
            CustomersSynchronizer::class,
            DocumentsImporter::class,
            DocumentsSynchronizer::class,
            AssetsImporter::class,
            AssetsSynchronizer::class,
        );
    }
}
