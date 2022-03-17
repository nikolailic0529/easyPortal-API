<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Services\Queue\Concerns\ProcessorJob;
use App\Services\Queue\Contracts\Progressable;
use App\Services\Queue\CronJob;
use App\Utils\Processor\Processor;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;

/**
 * Base Importer job.
 */
abstract class ImporterCronJob extends CronJob implements Progressable {
    /**
     * @phpstan-use \App\Services\Queue\Concerns\ProcessorJob<\App\Services\DataLoader\Importer\Importer>
     */
    use ProcessorJob {
        getProcessor as private createProcessor;
    }

    /**
     * @return array<mixed>
     */
    public function getQueueConfig(): array {
        return [
                'settings' => [
                    'chunk'  => null,
                    'expire' => null,
                ],
            ] + parent::getQueueConfig();
    }

    protected function getProcessor(Container $container, QueueableConfig $config): Processor {
        $expire    = $config->setting('expire');
        $from      = $expire ? Date::now()->sub($expire) : null;
        $processor = $this
            ->createProcessor($container, $config)
            ->setFrom($from);

        return $processor;
    }
}
