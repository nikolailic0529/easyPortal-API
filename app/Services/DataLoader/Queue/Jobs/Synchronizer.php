<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Queue\Jobs;

use App\Services\Queue\Concerns\ProcessorJob;
use App\Services\Queue\Contracts\Progressable;
use App\Services\Queue\CronJob;
use App\Utils\Processor\Contracts\Processor;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;

/**
 * @template TSynchronizer of \App\Services\DataLoader\Synchronizer\Synchronizer
 */
abstract class Synchronizer extends CronJob implements Progressable {
    /**
     * @phpstan-use ProcessorJob<TSynchronizer>
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
                    'chunk'           => null,
                    'expire'          => null,
                    'outdated'        => false,
                    'outdated_limit'  => null,
                    'outdated_expire' => null,
                ],
            ] + parent::getQueueConfig();
    }

    /**
     * @return TSynchronizer
     */
    protected function getProcessor(Container $container, QueueableConfig $config): Processor {
        $from           = $config->setting('expire')
            ? Date::now()->sub($config->setting('expire'))
            : null;
        $outdated       = (bool) $config->setting('outdated');
        $outdatedLimit  = $config->setting('outdated_limit')
            ? (int) $config->setting('outdated_limit')
            : null;
        $outdatedExpire = $config->setting('outdated_expire')
            ? Date::now()->sub($config->setting('outdated_expire'))
            : null;
        $processor      = $this
            ->createProcessor($container, $config)
            ->setFrom($from)
            ->setWithOutdated($outdated)
            ->setOutdatedLimit($outdatedLimit)
            ->setOutdatedExpire($outdatedExpire);

        return $processor;
    }
}
