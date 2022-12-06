<?php declare(strict_types = 1);

namespace App\Services\Queue\Concerns;

use App\Services\Queue\Exceptions\JobStopped as JobStoppedException;
use App\Services\Queue\Utils\Pinger;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\InteractsWithQueue;

/**
 * General implementation of {@link \App\Services\Queue\Contracts\Stoppable} Job.
 *
 * @mixin \LastDragon_ru\LaraASP\Queue\Queueables\Job
 */
trait PingableJob {
    use InteractsWithQueue;

    private ?Pinger $pinger;

    public function getJob(): Job {
        return $this->job;
    }

    private function getPinger(): ?Pinger {
        return $this->pinger;
    }

    private function setPinger(?Pinger $pinger): static {
        $this->pinger = $pinger;

        return $this;
    }

    final public function handle(Container $container, Pinger $pinger): void {
        try {
            $this->setPinger($pinger)->ping();
            $container->call($this);
        } catch (JobStoppedException) {
            // no action
        }
    }

    protected function ping(): void {
        $this->getPinger()?->ping($this);
    }
}
