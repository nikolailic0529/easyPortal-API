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
 * Base Importer job.
 *
 * @template TImporter of \App\Services\DataLoader\Importer\Importer
 */
abstract class Importer extends CronJob implements Progressable {
    /**
     * @phpstan-use ProcessorJob<TImporter>
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
                    'chunk'  => null,
                    'expire' => null,
                ],
            ] + parent::getQueueConfig();
    }

    /**
     * @return TImporter
     */
    protected function getProcessor(Container $container, QueueableConfig $config): Processor {
        $expire    = $config->setting('expire');
        $from      = $expire ? Date::now()->sub($expire) : null;
        $processor = $this
            ->createProcessor($container, $config)
            ->setFrom($from);

        return $processor;
    }
}
