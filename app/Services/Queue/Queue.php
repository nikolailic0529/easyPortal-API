<?php declare(strict_types = 1);

namespace App\Services\Queue;

use DateInterval;
use DateTimeInterface;
use Generator;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Laravel\Horizon\Contracts\JobRepository;
use LastDragon_ru\LaraASP\Queue\Queueables\Job;

use function array_fill_keys;
use function array_filter;
use function array_reverse;
use function count;
use function in_array;
use function json_decode;
use function reset;

class Queue {
    public function __construct(
        protected Container $container,
        protected Repository $cache,
        protected JobRepository $repository,
    ) {
        // empty
    }

    protected function getContainer(): Container {
        return $this->container;
    }

    public function isStopped(Job $job, string $id = null): bool {
        return $job instanceof Stoppable && (new Collection($this->getState($job)))
            ->first(static function (State $state) use ($id): bool {
                return $state->stopped && ($id === null || $state->id === $id);
            });
    }

    /**
     * Registers the stop request for the job. Please note that the method
     * doesn't stop the job, it just set the flag for the job.
     *
     * Only jobs that implement {@link \App\Services\Queue\Stoppable} can be
     * stopped.
     */
    public function stop(Job $job, string $id = null): bool {
        return $job instanceof Stoppable && $this->cache->set(
                $this->getStopKey($job, $id),
                Date::now()->timestamp,
                new DateInterval('P1W'),
            );
    }

    public function getName(Job $job): string {
        return $job instanceof NamedJob ? $job->displayName() : $job::class;
    }

    public function getProgress(Job $job): ?Progress {
        return $job instanceof Progressable
            ? $this->getContainer()->call($job->getProgressProvider())
            : null;
    }

    /**
     * @return array<\App\Services\Queue\State>
     */
    public function getState(Job $job): array {
        $states = $this->getStates([$job]);
        $state  = reset($states) ?: [];

        return $state;
    }

    /**
     * @param array<\LastDragon_ru\LaraASP\Queue\Queueables\Job> $jobs
     *
     * @return array<string,array<\App\Services\Queue\State>>
     */
    public function getStates(array $jobs): array {
        // Empty?
        if (!$jobs) {
            return [];
        }

        // Process
        $jobs     = (new Collection($jobs))
            ->keyBy(function (Job $job): string {
                return $this->getName($job);
            });
        $states   = array_fill_keys($jobs->keys()->all(), []);
        $statuses = [
            QueueJob::STATUS_PENDING,
            QueueJob::STATUS_RESERVED,
        ];

        foreach ($this->getPendingIterator() as $job) {
            /** @var \App\Services\Queue\QueueJob $job */

            // Not needed?
            if (!isset($states[$job->name]) || !in_array($job->status, $statuses, true)) {
                continue;
            }

            // Add
            $pendingAt = $this->getDate(json_decode($job->payload, true)['pushedAt'] ?? null);
            $stoppedAt = $this->getStoppedAt($jobs[$job->name], $job->id);
            $running   = $job->status === QueueJob::STATUS_RESERVED;
            $stopped   = $stoppedAt && $stoppedAt > $pendingAt;

            $states[$job->name][] = new State(
                $job->id,
                $job->name,
                $running,
                $stopped,
                $pendingAt,
                $this->getDate($job->reserved_at ?? null),
            );
        }

        // Sort (because jobs returned in reverse order)
        foreach ($states as $job => $values) {
            $states[$job] = array_reverse($values);
        }

        // Remove empty
        $states = array_filter($states);

        // Return
        return $states;
    }

    protected function getDate(string|bool|null $timestamp): ?DateTimeInterface {
        return $timestamp ? Date::createFromTimestamp((float) $timestamp) : null;
    }

    /**
     * @return \Generator<\App\Services\Queue\QueueJob>
     */
    protected function getPendingIterator(): Generator {
        $offset = null;

        do {
            /** @var array<\App\Services\Queue\QueueJob> $jobs */
            $jobs   = $this->repository->getPending($offset);
            $offset = $offset + count($jobs);

            foreach ($jobs as $job) {
                yield $job->id => $job;
            }
        } while (count($jobs) > 0);
    }

    protected function getStopKey(Job $job, string $id = null): string {
        return "service.queue.stop.{$this->getName($job)}".($id ? ".{$id}" : '');
    }

    protected function getStoppedAt(Job $job, string $id = null): ?DateTimeInterface {
        $stopped   = null;
        $timestamp = $this->cache->get($this->getStopKey($job, $id))
            ?: $this->cache->get($this->getStopKey($job));

        if ($timestamp) {
            $stopped = Date::createFromTimestamp($timestamp);
        }

        return $stopped;
    }
}
