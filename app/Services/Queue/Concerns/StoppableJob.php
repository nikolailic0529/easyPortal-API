<?php declare(strict_types = 1);

namespace App\Services\Queue\Concerns;

use App\Services\Queue\Events\JobStopped as JobStoppedEvent;
use App\Services\Queue\Exceptions\JobStopped as JobStoppedException;
use App\Services\Queue\Queue;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\InteractsWithQueue;

/**
 * General implementation of {@link \App\Services\Queue\Stoppable} Job.
 *
 * @mixin \LastDragon_ru\LaraASP\Queue\Queueables\Job
 */
trait StoppableJob {
    use InteractsWithQueue;

    protected ?Queue $service;

    protected function getJob(): Job {
        return $this->job;
    }

    protected function getService(): ?Queue {
        return $this->service;
    }

    protected function setService(?Queue $service): static {
        $this->service = $service;

        return $this;
    }

    final public function handle(Container $container, Dispatcher $dispatcher, Queue $service): void {
        try {
            $this->setService($service)->stop();
            $container->call($this);
        } catch (JobStoppedException) {
            if ($this->getJob()) {
                $dispatcher->dispatch(new JobStoppedEvent($this->getJob()));
            }
        }
    }

    /**
     * @throws \App\Services\Queue\Exceptions\JobStopped if job stopped
     */
    protected function stop(): void {
        if ($this->getService()?->isStopped($this, $this->getJob()->getJobId())) {
            throw new JobStoppedException();
        }
    }
}
