<?php declare(strict_types = 1);

namespace App\Services\Queue\Tags;

use App\Services\Queue\Contracts\Stoppable;
use App\Services\Queue\Service;
use App\Utils\Cache\CacheKeyable;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Queue\Jobs\Job;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;

use function filter_var;
use function microtime;

use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_FLOAT;

class Stop implements CacheKeyable {
    public function __construct(
        protected Application $app,
        protected Repository $cache,
        protected Service $service,
        protected MasterSupervisorRepository $repository,
    ) {
        // empty
    }

    public function isMarked(Stoppable $stoppable): bool {
        // Explicit?
        if ($this->isMarkedBySupervisor() || $this->isMarkedById($stoppable)) {
            return true;
        }

        // Is dispatch time known?
        $job        = $stoppable->getJob();
        $dispatched = $job instanceof Job
            ? $this->getTimestamp($job->payload()['pushedAt'] ?? null)
            : null;

        if ($dispatched === null) {
            return false;
        }

        // Return
        return $this->isMarkedByMarker($stoppable, $dispatched)
            || $this->isMarkedByQueueRestart($dispatched);
    }

    public function mark(Stoppable $job, ?string $id): bool {
        $key = [$this, $job];

        if ($id !== null) {
            $key[] = $id;
        }

        $this->service->set($key, microtime(true));

        return true;
    }

    protected function getTimestamp(mixed $timestamp): ?float {
        return filter_var($timestamp, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
    }

    protected function isMarkedById(Stoppable $stoppable): bool {
        // `id` is unique, so we don't need to check the time
        $id     = $stoppable->getJob()?->getJobId();
        $marked = $id !== null
            && $this->service->has([$this, $stoppable, $id]);

        return $marked;
    }

    protected function isMarkedByMarker(Stoppable $stoppable, float $dispatched): bool {
        $marker = $this->service->get([$this, $stoppable], function (mixed $timestamp): ?float {
            return $this->getTimestamp($timestamp);
        });
        $marked = $marker !== null && $marker > $dispatched;

        return $marked;
    }

    protected function isMarkedByQueueRestart(float $dispatched): bool {
        $restart = $this->getTimestamp($this->cache->get('illuminate:queue:restart'));
        $marked  = $restart !== null && $restart > $dispatched;

        return $marked;
    }

    protected function isMarkedBySupervisor(): bool {
        // We should not check Horizon status while testing or it will break some tests.
        if ($this->app->runningUnitTests()) {
            return false;
        }

        // The same trick as Horizon UI to detect if Horizon stopped.
        return !$this->repository->all();
    }
}
