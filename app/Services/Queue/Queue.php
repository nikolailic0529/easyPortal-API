<?php declare(strict_types = 1);

namespace App\Services\Queue;

use DateTimeInterface;
use Generator;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Laravel\Horizon\Contracts\JobRepository;
use LastDragon_ru\LaraASP\Queue\Queueables\Job;

use function array_fill_keys;
use function array_keys;
use function array_reverse;
use function count;
use function json_decode;
use function reset;

class Queue {
    public function __construct(
        protected Container $container,
        protected JobRepository $repository,
    ) {
        // empty
    }

    protected function getContainer(): Container {
        return $this->container;
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
        $jobs   = (new Collection($jobs))
            ->keyBy(function (Job $job): string {
                return $this->getName($job);
            })
            ->all();
        $states = array_fill_keys(array_keys($jobs), []);

        foreach ($this->getPendingIterator() as $job) {
            /** @var \App\Services\Queue\QueueJob $job */

            // Not needed?
            if (!isset($states[$job->name])) {
                continue;
            }

            // Add
            $states[$job->name][] = new State(
                $job->id,
                $job->name,
                $job->status === QueueJob::STATUS_RESERVED,
                $this->getDate(json_decode($job->payload, true)['pushedAt'] ?? null),
                $this->getDate($job->reserved_at ?? null),
            );
        }

        // Sort (because jobs returned in reverse order)
        foreach ($states as $job => $values) {
            $states[$job] = array_reverse($values);
        }

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
}
