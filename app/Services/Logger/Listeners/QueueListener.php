<?php declare(strict_types = 1);

namespace App\Services\Logger\Listeners;

use App\Services\Logger\Models\Enums\Category;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Queue;

use function array_pop;

class QueueListener extends Listener {
    /**
     * @var array<string>
     */
    protected array $stack = [];

    public function subscribe(Dispatcher $dispatcher): void {
        $dispatcher->listen(
            JobProcessing::class,
            $this->getSafeListener(function (JobProcessing $event): void {
                $this->started($event);
            }),
        );

        $dispatcher->listen(
            JobProcessed::class,
            $this->getSafeListener(function (JobProcessed $event): void {
                $this->success($event);
            }),
        );

        $dispatcher->listen(
            JobExceptionOccurred::class,
            $this->getSafeListener(function (JobExceptionOccurred $event): void {
                $this->failed($event);
            }),
        );

        Queue::createPayloadUsing(function (string $connection, string $queue, array $payload): array {
            $this->getSafeListener(function () use ($connection, $queue, $payload): void {
                $this->dispatched(new QueueJob($connection, $queue, $payload));
            })();

            return [];
        });
    }

    protected function dispatched(JobContract $job): void {
        $this->logger->event(
            Category::queue(),
            "job.dispatched: {$this->getName($job)}",
            null,
            $this->getContext($job),
            [
                'jobs.dispatched' => 1,
            ],
        );
    }

    protected function started(JobProcessing $event): void {
        $this->stack[] = $this->logger->start(
            Category::queue(),
            "job.processed: {$this->getName($event->job)}",
            $this->getContext($event->job),
        );
    }

    protected function success(JobProcessed $event): void {
        $transaction = array_pop($this->stack);

        if ($transaction) {
            $this->logger->success($transaction);
        }
    }

    protected function failed(JobExceptionOccurred $event): void {
        $transaction = array_pop($this->stack);

        if ($transaction) {
            $this->logger->fail(array_pop($this->stack), [
                'exception' => $event->exception?->getMessage(),
            ]);
        }
    }

    /**
     * @return array<string,mixed>
     */
    protected function getContext(JobContract $job): array {
        return [
            'id'         => $job->uuid(),
            'name'       => $this->getName($job),
            'connection' => $job->getConnectionName(),
            'queue'      => $job->getQueue(),
            'payload'    => $job->payload(),
        ];
    }

    protected function getName(JobContract $job): string {
        return ($job->payload()['displayName'] ?? '') ?: $job->getName();
    }
}
