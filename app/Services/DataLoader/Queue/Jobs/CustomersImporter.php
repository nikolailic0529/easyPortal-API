<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Queue\Jobs;

use App\Services\DataLoader\Importer\Importers\Customers\Importer;
use App\Services\DataLoader\Queue\Jobs\Importer as ImporterJob;
use App\Utils\Processor\Contracts\Processor;
use Config\Constants;
use Illuminate\Contracts\Container\Container;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;

/**
 * @extends ImporterJob<Importer>
 */
class CustomersImporter extends ImporterJob {
    public function displayName(): string {
        return 'ep-data-loader-customers-importer';
    }

    /**
     * @return array<string, mixed>
     */
    public function getQueueConfig(): array {
        return [
                'settings' => [
                    'chunk'  => Constants::EP_DATA_LOADER_CUSTOMERS_IMPORTER_CHUNK,
                    'expire' => null,
                ],
            ] + parent::getQueueConfig();
    }

    protected function makeProcessor(Container $container, QueueableConfig $config): Processor {
        return $container->make(Importer::class);
    }
}
