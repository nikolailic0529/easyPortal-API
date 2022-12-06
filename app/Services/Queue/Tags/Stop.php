<?php declare(strict_types = 1);

namespace App\Services\Queue\Tags;

use App\Services\Queue\Contracts\Stoppable;
use App\Services\Queue\Service;
use App\Utils\Cache\CacheKeyable;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Queue\Jobs\Job;

use function filter_var;
use function microtime;

use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_FLOAT;

class Stop implements CacheKeyable {
    public function __construct(
        protected Repository $cache,
        protected Service $service,
    ) {
        // empty
    }

    public function isMarked(Stoppable $stoppable): bool {
        // Explicit? (`id` is unique, so we don't need to check the time)
        $job = $stoppable->getJob();
        $id  = $job->getJobId();

        if ($this->service->has([$this, $stoppable, $id])) {
            return true;
        }

        // Is dispatch time known?
        $dispatched = $job instanceof Job
            ? $this->getTimestamp($job->payload()['pushedAt'] ?? null)
            : null;

        if ($dispatched === null) {
            return false;
        }

        // Marker?
        $marker = $this->service->get([$this, $stoppable], function (mixed $timestamp): ?float {
            return $this->getTimestamp($timestamp);
        });

        if ($marker !== null && $marker >= $dispatched) {
            return true;
        }

        // Restart?
        $restart = $this->getTimestamp($this->cache->get('illuminate:queue:restart'));

        if ($restart !== null && $restart >= $dispatched) {
            return true;
        }

        // Return
        return false;
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
}
