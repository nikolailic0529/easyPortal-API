<?php declare(strict_types = 1);

namespace App\Services\Queue;

use App\Services\Logger\Models\Enums\Action;
use App\Services\Logger\Models\Enums\Category;
use App\Services\Logger\Models\Enums\Status;
use App\Services\Logger\Models\Log;
use DateInterval;
use DateTimeInterface;
use Generator;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Laravel\Horizon\Contracts\JobRepository;
use LastDragon_ru\LaraASP\Queue\Queueables\Job;

use function array_fill_keys;
use function array_filter;
use function array_merge_recursive;
use function array_reverse;
use function array_values;
use function count;
use function in_array;
use function json_decode;
use function reset;
use function usort;

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
        return $job instanceof Stoppable
            && $this->cache->set(
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
            ? $this->getContainer()->call($job->getProgressCallback())
            : null;
    }

    public function resetProgress(Job $job): bool {
        return $job instanceof Progressable
            && $this->getContainer()->call($job->getResetProgressCallback());
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

        // Prepare
        $jobs   = (new Collection($jobs))
            ->keyBy(function (Job $job): string {
                return $this->getName($job);
            });
        $states = array_fill_keys($jobs->keys()->all(), []);
        $states = array_merge_recursive($states, $this->getStatesFromHorizon($jobs, $states));
        $states = array_merge_recursive($states, $this->getStatesFromLogs($jobs, $states));

        // Sort
        foreach ($states as &$values) {
            usort($values, static function (State $a, State $b): int {
                return $b->running_at <=> $a->running_at;
            });
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
        return "service:queue:stop:{$this->getName($job)}".($id ? "#{$id}" : '');
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

    /**
     * @param \Illuminate\Support\Collection<string,\LastDragon_ru\LaraASP\Queue\Queueables\Job> $jobs
     * @param array<string,array<string,\App\Services\Queue\State|null>>                         $states
     *
     * @return array<string,\App\Services\Queue\State|null>
     */
    protected function getStatesFromHorizon(Collection $jobs, array $states): array {
        $statuses = [
            QueueJob::STATUS_PENDING,
            QueueJob::STATUS_RESERVED,
        ];

        foreach ($this->getPendingIterator() as $job) {
            /** @var \App\Services\Queue\QueueJob $job */

            // Not needed?
            if (!isset($jobs[$job->name]) || !in_array($job->status, $statuses, true)) {
                continue;
            }

            // Exists?
            if (isset($states[$job->name][$job->id])) {
                continue;
            }

            // Add
            $pendingAt = $this->getDate(json_decode($job->payload, true)['pushedAt'] ?? null);
            $stoppedAt = $this->getStoppedAt($jobs[$job->name], $job->id);
            $running   = $job->status === QueueJob::STATUS_RESERVED;
            $stopped   = $this->getStatesIsStopped($pendingAt, $stoppedAt);

            $states[$job->name][$job->id] = new State(
                $job->id,
                $job->name,
                $running,
                $stopped,
                $pendingAt,
                $this->getDate($job->reserved_at ?? null),
            );
        }

        return $states;
    }

    /**
     * @param \Illuminate\Support\Collection<string,\LastDragon_ru\LaraASP\Queue\Queueables\Job> $jobs
     * @param array<string,array<string,\App\Services\Queue\State|null>>                         $states
     *
     * @return array<string,\App\Services\Queue\State|null>
     */
    protected function getStatesFromLogs(Collection $jobs, array $states): array {
        // Depending on Horizon `trim` settings the job can be removed from the
        // pending list but it may still run. Thus we should also check our
        // logs to make sure that the state is correct.

        // Jobs to check
        $names = $jobs->keys();

        if (!$names) {
            return $states;
        }

        // Get status
        $key     = static function (Log $log): string {
            return "{$log->object_type}#{$log->object_id}";
        };
        $logs    = Log::query()
            ->where('category', '=', Category::queue())
            ->where('action', '=', Action::queueJobRun())
            ->where('status', '=', Status::active())
            ->whereIn('object_type', $names)
            ->get();
        $pending = Log::query()
            ->where('category', '=', Category::queue())
            ->where('action', '=', Action::queueJobDispatched())
            ->where('status', '=', Status::success())
            ->where(static function (Builder $builder) use ($logs): void {
                foreach ($logs as $log) {
                    $builder->orWhere(static function (Builder $builder) use ($log): void {
                        $builder->where('object_type', '=', $log->object_type);
                        $builder->where('object_id', '=', $log->object_id);
                    });
                }
            })
            ->get()
            ->keyBy($key);

        foreach ($logs as $log) {
            // Exists?
            $id   = $log->object_id;
            $name = $log->object_type;

            if (isset($states[$name][$id])) {
                continue;
            }

            // Add
            $pendingAt = $pending[$key($log)]->created_at ?? null;
            $stoppedAt = $this->getStoppedAt($jobs[$name], $id);
            $running   = true;
            $stopped   = $this->getStatesIsStopped($pendingAt, $stoppedAt);

            $states[$name][$id] = new State(
                $id,
                $name,
                $running,
                $stopped,
                $pendingAt,
                $log->created_at,
            );
        }

        // Return
        return $states;
    }

    protected function getStatesIsStopped(?DateTimeInterface $pendingAt, ?DateTimeInterface $stoppedAt): bool {
        return $stoppedAt && $pendingAt && $stoppedAt > $pendingAt;
    }
}
