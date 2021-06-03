<?php declare(strict_types = 1);

namespace App\Services\Logger\Listeners;

use App\Events\Subscriber;
use App\Services\Logger\Logger;
use App\Services\Logger\Models\Enums\Category;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Queue\Queue;

use function array_pop;
use function json_encode;

class QueueListener implements Subscriber {
    /**
     * @var array<string>
     */
    protected array $stack = [];

    public function __construct(
        protected Logger $logger,
    ) {
        // empty
    }

    public function subscribe(Dispatcher $dispatcher): void {
        $dispatcher->listen(JobProcessing::class, function (JobProcessing $event): void {
            $this->started($event);
        });

        $dispatcher->listen(JobProcessed::class, function (JobProcessed $event): void {
            $this->success($event);
        });

        $dispatcher->listen(JobExceptionOccurred::class, function (JobExceptionOccurred $event): void {
            $this->failed($event);
        });

        Queue::createPayloadUsing(function (string $connection, string $queue, array $payload): array {
            $this->dispatched(new class($connection, $queue, $payload) extends Job implements JobContract {
                /**
                 * @inheritDoc
                 *
                 * @param array<mixed> $payload
                 */
                public function __construct(
                    protected $connectionName,
                    protected $queue,
                    protected array $payload,
                ) {
                    // empty
                }

                /**
                 * @return array<mixed>
                 */
                public function payload(): array {
                    return $this->payload;
                }

                public function getJobId(): string|null {
                    return $this->payload()['id'] ?? null;
                }

                public function getRawBody(): string {
                    return json_encode($this->payload());
                }

                public function attempts(): int {
                    return ($this->payload()['attempts'] ?? 0) + 1;
                }
            });

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
