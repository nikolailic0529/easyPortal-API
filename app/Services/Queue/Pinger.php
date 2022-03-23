<?php declare(strict_types = 1);

namespace App\Services\Queue;

use App\Services\Queue\Events\JobStopped as JobStoppedEvent;
use App\Services\Queue\Exceptions\JobStopped;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\Jobs\RedisJob;
use Illuminate\Queue\RedisQueue;

class Pinger {
    public function __construct(
        protected Dispatcher $dispatcher,
        protected Queue $service,
    ) {
        // empty
    }

    public function ping(CronJob|Job $job): void {
        $this->stop($job);
        $this->prolong($job);
    }

    /**
     * Checks status of the job and throws exceptions if job marked as stopped.
     *
     * @see \App\Services\Queue\Exceptions\JobStopped
     */
    protected function stop(CronJob|Job $job): void {
        if ($this->service->isStopped($job, $job->getJob()->getJobId())) {
            $this->dispatcher->dispatch(new JobStoppedEvent($job->getJob()));

            throw new JobStopped();
        }
    }

    /**
     * Prolongs expiration time for the job. It is required to prevent rerun
     * the job when running time is bigger than `retry_after` (it is not
     * possible to set `retry_after` high because in this case job will
     * be restarted after a very long time).
     */
    protected function prolong(CronJob|Job $job): void {
        // Possible?
        $queueJob = $job->getJob();

        if (!($queueJob instanceof RedisJob)) {
            return;
        }

        // Prolong
        $queue      = $queueJob->getRedisQueue();
        $retryAfter = (new class() extends RedisQueue {
            /**
             * @noinspection PhpMissingParentConstructorInspection
             * @phpstan-ignore-next-line
             */
            public function __construct() {
                // small hack to get access protected members
            }

            public function getRetryAfter(RedisQueue $queue): int {
                return $queue->availableAt((int) $queue->retryAfter);
            }
        })->getRetryAfter($queue);

        $queue->getConnection()->zadd(
            "{$queue->getQueue($queueJob->getQueue())}:reserved",
            $retryAfter,
            $queueJob->getReservedJob(),
        );
    }
}
