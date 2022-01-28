<?php declare(strict_types = 1);

namespace App\Services\Queue\Concerns;

use App\Services\Queue\Exceptions\JobStopped;
use App\Services\Queue\Progress;
use App\Services\Service;
use App\Utils\Processor\Processor;
use Illuminate\Contracts\Container\Container;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;

/**
 * Special helper for {@see \App\Utils\Processor\Processor}.
 *
 * @mixin \App\Services\Queue\Job
 * @mixin \App\Services\Queue\CronJob
 * @implements \App\Services\Queue\Progressable
 */
trait ProcessorJob {
    /**
     * @return array<mixed>
     */
    public function getQueueConfig(): array {
        return [
                'settings' => [
                    'chunk' => null,
                ],
            ] + parent::getQueueConfig();
    }

    public function __invoke(Container $container, QueueableConfigurator $configurator): void {
        $processor = $this->getProcessor($container);
        $service   = $container->make(Service::getService($this));
        $config    = $configurator->config($this);
        $chunk     = $config->setting('chunk');

        $processor
            ->setChunkSize($chunk)
            ->setCacheKey($service, $this)
            ->onChange(function () use ($processor): void {
                try {
                    $this->ping();
                } catch (JobStopped) {
                    $processor->stop();
                }
            })
            ->start();
    }

    public function getProgressCallback(): callable {
        return function (Container $container): ?Progress {
            $progress = null;
            $state    = $this->getProcessor($container)->getState();

            if ($state) {
                $progress = new Progress($state->total, $state->processed);
            }

            return $progress;
        };
    }

    public function getResetProgressCallback(): callable {
        return function (Container $container): bool {
            $this->getProcessor($container)->reset();

            return true;
        };
    }

    abstract protected function ping(): void;

    abstract protected function getProcessor(Container $container): Processor;
}
