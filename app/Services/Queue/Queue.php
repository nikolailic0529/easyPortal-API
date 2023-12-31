<?php declare(strict_types = 1);

namespace App\Services\Queue;

use App\Services\Logger\Models\Enums\Action;
use App\Services\Logger\Models\Enums\Category;
use App\Services\Logger\Models\Enums\Status;
use App\Services\Logger\Models\Log;
use App\Services\Queue\Contracts\NamedJob;
use App\Services\Queue\Contracts\Progressable;
use App\Services\Queue\Contracts\Stoppable;
use App\Services\Queue\Tags\Stop;
use AppendIterator;
use DateInterval;
use DateTimeInterface;
use Generator;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Laravel\Horizon\Contracts\JobRepository;
use LastDragon_ru\LaraASP\Queue\Queueables\Job;
use NoRewindIterator;

use function array_fill_keys;
use function array_filter;
use function config;
use function count;
use function in_array;
use function is_int;
use function json_decode;
use function reset;
use function usort;

class Queue {
    public function __construct(
        protected Stop $stop,
        protected JobRepository $repository,
    ) {
        // empty
    }

    public function isStopped(?Job $job): bool {
        // Depending on Horizon `trim` settings the job can be removed from the
        // pending list but it may still run. In this case, `$job` will `null`,
        // and, unfortunately, I'm not sure how we should handle this case.
        return $job instanceof Stoppable
            && $this->stop->isMarked($job);
    }

    /**
     * Registers the stop request for the job. Please note that the method
     * doesn't stop the job, it just set the flag for the job.
     *
     * Only jobs that implement {@link \App\Services\Queue\Contracts\Stoppable} can be
     * stopped.
     */
    public function stop(Job $job, string $id = null): bool {
        return $job instanceof Stoppable
            && $this->stop->mark($job, $id);
    }

    public function getName(Job $job): string {
        return $job instanceof NamedJob ? $job->displayName() : $job::class;
    }

    public function getProgress(Job $job): ?Progress {
        return $job instanceof Progressable
            ? Container::getInstance()->call($job->getProgressCallback())
            : null;
    }

    public function resetProgress(Job $job): bool {
        return $job instanceof Progressable
            && Container::getInstance()->call($job->getResetProgressCallback());
    }

    /**
     * @return array<JobState>
     */
    public function getState(Job $job): array {
        $states = $this->getStates([$job]);
        $state  = reset($states) ?: [];

        return $state;
    }

    /**
     * @param array<Job> $jobs
     *
     * @return array<string,array<JobState>>
     */
    public function getStates(array $jobs): array {
        // Empty?
        if (!$jobs) {
            return [];
        }

        // Prepare
        $jobs     = (new Collection($jobs))
            ->keyBy(function (Job $job): string {
                return $this->getName($job);
            });
        $states   = array_fill_keys($jobs->keys()->all(), []);
        $iterator = new AppendIterator();
        $iterator->append(new NoRewindIterator($this->getStatesFromHorizon($jobs)));
        $iterator->append(new NoRewindIterator($this->getStatesFromLogs($jobs)));

        foreach ($iterator as $state) {
            /** @var JobState $state */
            if (!isset($states[$state->name][$state->id])) {
                $states[$state->name][$state->id] = $state;
            }
        }

        // Sort
        foreach ($states as &$values) {
            usort($values, static function (JobState $a, JobState $b): int {
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
     * @return Generator<QueueJob>
     */
    protected function getPendingIterator(): Generator {
        $offset = null;

        do {
            /** @var array<QueueJob> $jobs */
            $jobs   = $this->repository->getPending($offset);
            $offset = (int) $offset + count($jobs);

            foreach ($jobs as $job) {
                yield $job->id => $job;
            }
        } while (count($jobs) > 0);
    }

    /**
     * @param Collection<string,Job> $jobs
     *
     * @return Generator<JobState>
     */
    protected function getStatesFromHorizon(Collection $jobs): Generator {
        $statuses = [
            QueueJob::STATUS_PENDING,
            QueueJob::STATUS_RESERVED,
        ];

        foreach ($this->getPendingIterator() as $job) {
            /** @var QueueJob $job */

            // Not needed?
            if (!isset($jobs[$job->name]) || !in_array($job->status, $statuses, true)) {
                continue;
            }

            // Return
            yield new JobState(
                $job->name,
                $job->id,
                $job->status === QueueJob::STATUS_RESERVED,
                $this->isStopped($jobs[$job->name]),
                $this->getDate(json_decode($job->payload, true)['pushedAt'] ?? null),
                $this->getDate($job->reserved_at ?? null),
            );
        }
    }

    /**
     * @param Collection<string, Job> $jobs
     *
     * @return Generator<JobState>
     */
    protected function getStatesFromLogs(Collection $jobs): Generator {
        // Depending on Horizon `trim` settings the job can be removed from the
        // pending list but it may still run. Thus we should also check our
        // logs to make sure that the state is correct.

        // Empty?
        if ($jobs->isEmpty()) {
            yield from [];
        }

        // Get status
        $key     = static function (Log $log): string {
            return "{$log->object_type}#{$log->object_id}";
        };
        $names   = $jobs->keys();
        $logs    = $this->getStatesFromLogsActive($names);
        $pending = $this->getStatesFromLogsDispatched($logs)->keyBy($key);

        foreach ($logs as $log) {
            // Exists?
            $id   = $log->object_id;
            $name = $log->object_type;

            if ($name === null || $id === null) {
                continue;
            }

            // Return
            yield new JobState(
                $name,
                $id,
                true,
                $this->isStopped($jobs[$name]),
                $pending[$key($log)]->created_at ?? null,
                $log->created_at,
            );
        }
    }

    protected function getStatesFromLogsExpire(): ?DateInterval {
        $connection = config('queue.default');
        $retryAfter = config("queue.connections.{$connection}.retry_after");
        $interval   = null;

        if ($retryAfter && is_int($retryAfter)) {
            $interval = Date::now()->subSeconds($retryAfter)->diff(Date::now());
        }

        return $interval;
    }

    /**
     * @param Collection<int, string> $names
     *
     * @return Collection<int, Log>
     */
    private function getStatesFromLogsActive(Collection $names): Collection {
        $expire = $this->getStatesFromLogsExpire();
        $logs   = Log::query()
            ->where('category', '=', Category::queue())
            ->where('action', '=', Action::queueJobRun())
            ->where('status', '=', Status::active())
            ->whereIn('object_type', $names)
            ->when($expire, static function (Builder $builder) use ($expire): void {
                $builder->where('updated_at', '>', Date::now()->sub($expire));
            })
            ->orderBy('created_at')
            ->get();

        return $logs;
    }

    /**
     * @param Collection<int, Log> $logs
     *
     * @return Collection<int, Log>
     */
    private function getStatesFromLogsDispatched(Collection $logs): Collection {
        $collection = new Collection();

        if (!$logs->isEmpty()) {
            $collection = Log::query()
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
                ->get();
        }

        return $collection;
    }
}
