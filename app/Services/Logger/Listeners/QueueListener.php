<?php declare(strict_types = 1);

namespace App\Services\Logger\Listeners;

use App\Services\Logger\Models\Enums\Category;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Queue;

use function array_pop;
use function last;

class QueueListener extends Listener {
    /**
     * @var array<array<string,string>>
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
            JobFailed::class,
            $this->getSafeListener(function (JobFailed $event): void {
                $this->failed($event);
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
            'job.dispatched',
            new QueueObject($job),
            $this->getContext($job),
            [
                'jobs.dispatched' => 1,
            ],
        );
    }

    protected function started(JobProcessing $event): void {
        $this->stack[] = [
            $event->job->uuid(),
            $this->logger->start(
                Category::queue(),
                'job.processed',
                new QueueObject($event->job),
                $this->getContext($event->job),
            ),
        ];
    }

    protected function success(JobProcessed $event): void {
        [$id, $transaction] = last($this->stack);

        if ($id === $event->job->uuid() && $transaction) {
            array_pop($this->stack);

            $this->logger->success($transaction);
        }
    }

    protected function failed(JobExceptionOccurred|JobFailed $event): void {
        [$id, $transaction] = last($this->stack);

        if ($id === $event->job->uuid() && $transaction) {
            array_pop($this->stack);

            $this->logger->fail($transaction, [
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
            'connection' => $job->getConnectionName(),
            'queue'      => $job->getQueue(),
            'payload'    => $job->payload(),
        ];
    }
}
