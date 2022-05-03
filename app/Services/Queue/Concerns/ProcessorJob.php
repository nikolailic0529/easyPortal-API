<?php declare(strict_types = 1);

namespace App\Services\Queue\Concerns;

use App\Services\Queue\Contracts\Progressable;
use App\Services\Queue\CronJob;
use App\Services\Queue\Exceptions\JobStopped;
use App\Services\Queue\Job;
use App\Services\Queue\Progress;
use App\Services\Service;
use App\Utils\Processor\Processor;
use App\Utils\Processor\ServiceStore;
use Illuminate\Contracts\Container\Container;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;

/**
 * Special helper for {@see \App\Utils\Processor\Processor}.
 *
 * @template TProcessor of \App\Utils\Processor\Processor
 *
 * @mixin Job
 */
trait ProcessorJob {
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

    public function __invoke(Container $container, QueueableConfigurator $configurator): void {
        $config    = $configurator->config($this);
        $processor = $this->getProcessor($container, $config);

        $processor
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
        return function (Container $container, QueueableConfigurator $configurator): ?Progress {
            $progress = null;
            $config   = $configurator->config($this);
            $state    = $this->getProcessor($container, $config)->getState();

            if ($state) {
                $progress = new Progress($state->total, $state->processed);
            }

            return $progress;
        };
    }

    public function getResetProgressCallback(): callable {
        return function (Container $container, QueueableConfigurator $configurator): bool {
            $this->getProcessor($container, $configurator->config($this))->reset();

            return true;
        };
    }

    protected function getService(Container $container): ?Service {
        return $container->make(Service::getService($this));
    }

    /**
     * @return TProcessor
     */
    protected function getProcessor(Container $container, QueueableConfig $config): Processor {
        $chunk     = $config->setting('chunk');
        $service   = $this->getService($container);
        $processor = $this
            ->makeProcessor($container, $config)
            ->setChunkSize($chunk);

        if ($this instanceof Progressable && $service) {
            if ($this instanceof CronJob) {
                $processor = $processor->setStore(new ServiceStore($service, $this));
            } else {
                $processor = $processor->setStore(new ServiceStore($service, [
                    $this,
                    $this->getJob()->getJobId(),
                ]));
            }
        }

        return $processor;
    }

    abstract protected function ping(): void;

    /**
     * @return TProcessor
     */
    abstract protected function makeProcessor(Container $container, QueueableConfig $config): Processor;
}
