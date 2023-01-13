<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Queue\Jobs;

use App\Services\DataLoader\Processors\Synchronizer\Synchronizer;
use App\Services\Queue\Concerns\ProcessorJob;
use App\Services\Queue\Contracts\Progressable;
use App\Services\Queue\CronJob;
use App\Utils\Processor\Contracts\Processor;
use Illuminate\Contracts\Container\Container;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;

/**
 * @template TSynchronizer of Synchronizer
 */
abstract class Importer extends CronJob implements Progressable {
    /**
     * @use ProcessorJob<TSynchronizer>
     */
    use ProcessorJob {
        getProcessor as private createProcessor;
    }

    /**
     * @return array<string, mixed>
     */
    public function getQueueConfig(): array {
        return [
                'settings' => [
                    'chunk' => null,
                ],
            ] + parent::getQueueConfig();
    }

    /**
     * @return TSynchronizer
     */
    protected function getProcessor(Container $container, QueueableConfig $config): Processor {
        return $this
            ->createProcessor($container, $config)
            ->setFrom(null)
            ->setForce(false)
            ->setWithOutdated(true)
            ->setOutdatedLimit(null)
            ->setOutdatedExpire(null);
    }
}
